import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';

export default function ManageClientLogin({
    open,
    onClose,
    client,
    mode = 'create',
}) {
    const isCreate = mode === 'create';
    const { data, setData, post, processing, errors, reset } = useForm({
        email: client.email || '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        const url = isCreate
            ? route('clients.login.store', client.id)
            : route('clients.login.reset-password', client.id);

        post(url, {
            preserveScroll: true,
            onSuccess: () => {
                reset('password', 'password_confirmation');
                onClose();
            },
        });
    };

    return (
        <Drawer
            open={open}
            onClose={onClose}
            title={isCreate ? 'Create login' : 'Reset password'}
            footer={
                <div className="flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Cancel
                    </button>
                    <button type="submit" form="client-login-form" className="crm-btn-primary" disabled={processing}>
                        {isCreate ? 'Create login' : 'Reset password'}
                    </button>
                </div>
            }
        >
            <form id="client-login-form" onSubmit={submit} className="space-y-4">
                {isCreate && (
                    <div>
                        <label className="crm-label">Email</label>
                        <input
                            type="email"
                            className="crm-input"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <InputError message={errors.email} className="mt-1" />
                        <p className="mt-1 text-xs text-ink-muted">Used for portal login. Temporary password is set below.</p>
                    </div>
                )}
                <div>
                    <label className="crm-label">Temporary password</label>
                    <input
                        type="password"
                        className="crm-input"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} className="mt-1" />
                </div>
                <div>
                    <label className="crm-label">Confirm password</label>
                    <input
                        type="password"
                        className="crm-input"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                        autoComplete="new-password"
                    />
                </div>
            </form>
        </Drawer>
    );
}
