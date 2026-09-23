/**
 * Grovera Studio CRM — optional Node helper
 *
 * This is a Laravel 10 (PHP) application.
 * Production entry point on cPanel is:  public/index.php
 * Do NOT deploy this app as a "Node.js App" in cPanel.
 *
 * Use this file only for LOCAL development if you prefer:
 *   node server.js
 *
 * Prefer the normal Laravel way:
 *   php artisan serve
 */

const { spawn } = require('child_process');
const path = require('path');

const host = process.env.HOST || '127.0.0.1';
const port = process.env.PORT || '8000';
const root = __dirname;

console.log('Grovera Studio CRM');
console.log('Starting PHP built-in server via artisan...');
console.log(`URL: http://${host}:${port}`);
console.log('');
console.log('cPanel note: point the domain document root to /public');
console.log('             and run PHP 8.1+ — not Node.js Application.');
console.log('');

const child = spawn(
    'php',
    ['artisan', 'serve', `--host=${host}`, `--port=${port}`],
    {
        cwd: root,
        stdio: 'inherit',
        shell: true,
    }
);

child.on('error', (err) => {
    console.error('Failed to start. Is PHP in your PATH?');
    console.error(err.message);
    process.exit(1);
});

child.on('exit', (code) => {
    process.exit(code ?? 0);
});

process.on('SIGINT', () => {
    child.kill('SIGINT');
});

process.on('SIGTERM', () => {
    child.kill('SIGTERM');
});
