<?php

namespace App\Http\Controllers;

use App\Enums\InfluencerType;
use App\Models\ImportJob;
use App\Services\Influencer\DuplicateDetectionService;
use App\Services\Influencer\InfluencerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function __construct(
        protected InfluencerService $influencerService,
        protected DuplicateDetectionService $duplicateDetection
    ) {
        $this->middleware('permission:imports.manage|influencers.import');
    }

    public function index(): Response
    {
        $jobs = ImportJob::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Import/Index', [
            'jobs' => $jobs,
            'mappableFields' => $this->mappableFields(),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'type' => ['nullable', 'string', 'in:influencers'],
        ]);

        $file = $request->file('file');
        $path = $file->store('imports');

        $rows = $this->readSpreadsheet(Storage::path($path));
        $headers = array_shift($rows) ?? [];

        $job = ImportJob::create([
            'user_id' => $request->user()->id,
            'type' => $request->get('type', 'influencers'),
            'filename' => $file->getClientOriginalName(),
            'status' => 'uploaded',
            'total_rows' => count($rows),
            'mapping' => [
                'storage_path' => $path,
                'headers' => $headers,
                'preview' => array_slice($rows, 0, 5),
            ],
        ]);

        return redirect()
            ->route('import.map', $job)
            ->with('success', 'File uploaded. Map columns to continue.');
    }

    public function map(ImportJob $importJob): Response
    {
        abort_unless($importJob->user_id === auth()->id(), 403);

        return Inertia::render('Import/Map', [
            'job' => $importJob,
            'mappableFields' => $this->mappableFields(),
            'headers' => $importJob->mapping['headers'] ?? [],
            'preview' => $importJob->mapping['preview'] ?? [],
        ]);
    }

    public function saveMapping(Request $request, ImportJob $importJob): RedirectResponse
    {
        abort_unless($importJob->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.name' => ['required', 'string'],
        ]);

        $mapping = $importJob->mapping ?? [];
        $mapping['field_map'] = $validated['mapping'];

        $importJob->update([
            'mapping' => $mapping,
            'status' => 'mapped',
        ]);

        return redirect()->route('import.preview', $importJob);
    }

    public function preview(ImportJob $importJob): Response
    {
        abort_unless($importJob->user_id === auth()->id(), 403);

        $rows = $this->mappedRows($importJob, 10);

        return Inertia::render('Import/Preview', [
            'job' => $importJob,
            'rows' => $rows,
        ]);
    }

    public function process(ImportJob $importJob): RedirectResponse
    {
        abort_unless($importJob->user_id === auth()->id(), 403);

        $rows = $this->mappedRows($importJob);
        $errors = [];
        $success = 0;
        $processed = 0;

        $importJob->update(['status' => 'processing']);

        foreach ($rows as $index => $row) {
            $processed++;

            try {
                if (empty($row['name'])) {
                    throw new \RuntimeException('Name is required.');
                }

                if (empty($row['influencer_type'])) {
                    $row['influencer_type'] = InfluencerType::Medium->value;
                }

                $this->influencerService->create($row, true);
                $success++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $importJob->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'success_count' => $success,
            'error_count' => count($errors),
            'errors' => $errors,
        ]);

        return redirect()
            ->route('import.index')
            ->with('success', "Import completed: {$success} succeeded, ".count($errors).' failed.');
    }

    protected function mappableFields(): array
    {
        return [
            'name' => 'Influencer Name',
            'instagram_url' => 'Instagram Link',
            'instagram_username' => 'Instagram Username',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'location' => 'Location',
            'influencer_type' => 'Influencer Type',
            'default_price' => 'Default Price',
            'notes_summary' => 'Notes',
        ];
    }

    protected function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $row) {
            if (collect($row)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }
            $rows[] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row);
        }

        return $rows;
    }

    protected function mappedRows(ImportJob $importJob, ?int $limit = null): array
    {
        $path = Storage::path($importJob->mapping['storage_path'] ?? '');
        $fieldMap = $importJob->mapping['field_map'] ?? [];
        $headers = $importJob->mapping['headers'] ?? [];

        $all = $this->readSpreadsheet($path);
        array_shift($all);

        $headerIndex = [];
        foreach ($headers as $i => $header) {
            $headerIndex[(string) $header] = $i;
        }

        $mapped = [];
        foreach ($all as $row) {
            $item = [];
            foreach ($fieldMap as $field => $header) {
                $idx = $headerIndex[$header] ?? null;
                $item[$field] = $idx !== null ? ($row[$idx] ?? null) : null;
            }
            $mapped[] = $item;

            if ($limit && count($mapped) >= $limit) {
                break;
            }
        }

        return $mapped;
    }
}
