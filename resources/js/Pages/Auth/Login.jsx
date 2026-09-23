import { useEffect, useState } from 'react';
import Checkbox from '@/Components/Checkbox';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';

export default function Login({ status, canResetPassword, canRegister = false }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => () => reset('password'), []);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'));
    };

    return (
        <GuestLayout>
            <Head title="Sign in" />

            <div className="mb-6">
                <h1 className="font-display text-2xl font-semibold text-ink">Welcome back</h1>
                <p className="mt-1 text-sm text-ink-muted">Sign in with your email or username</p>
            </div>

            {status && <div className="mb-4 rounded-lg bg-accent-soft px-3 py-2 text-sm font-medium text-accent-hover">{status}</div>}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="email" value="Email or username" className="!text-ink-soft" />
                    <TextInput
                        id="email"
                        type="text"
                        name="email"
                        value={data.email}
                        className="mt-1.5 block w-full rounded-lg border-surface-border focus:border-accent focus:ring-accent/30"
                        autoComplete="username"
                        isFocused
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div>
                    <div className="flex items-center justify-between">
                        <InputLabel htmlFor="password" value="Password" className="!text-ink-soft" />
                        {canResetPassword && (
                            <Link href={route('password.request')} className="text-xs font-medium text-accent hover:text-accent-hover">
                                Forgot password?
                            </Link>
                        )}
                    </div>
                    <div className="relative mt-1.5">
                        <TextInput
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            className="block w-full rounded-lg border-surface-border pr-10 focus:border-accent focus:ring-accent/30"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <button
                            type="button"
                            className="absolute inset-y-0 right-0 flex items-center px-3 text-ink-muted hover:text-ink"
                            onClick={() => setShowPassword((v) => !v)}
                            tabIndex={-1}
                        >
                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                        </button>
                    </div>
                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <label className="flex items-center gap-2">
                    <Checkbox name="remember" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                    <span className="text-sm text-ink-muted">Remember me</span>
                </label>

                <button type="submit" className="crm-btn-primary w-full !py-2.5" disabled={processing}>
                    Sign in
                </button>

                {canRegister && (
                    <p className="text-center text-sm text-ink-muted">
                        No account?{' '}
                        <Link href={route('register')} className="font-medium text-accent hover:text-accent-hover">
                            Register
                        </Link>
                    </p>
                )}
            </form>
        </GuestLayout>
    );
}
