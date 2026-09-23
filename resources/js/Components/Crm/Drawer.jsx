import { Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { X } from 'lucide-react';
import { cn } from '@/lib/format';

export default function Drawer({ open, onClose, title, children, footer, width = 'max-w-md' }) {
    return (
        <Transition show={open} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-ink/40" />
                </Transition.Child>

                <div className="fixed inset-0 overflow-hidden">
                    <div className="absolute inset-0 overflow-hidden">
                        <div className="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                            <Transition.Child
                                as={Fragment}
                                enter="transform transition ease-out duration-200"
                                enterFrom="translate-x-full"
                                enterTo="translate-x-0"
                                leave="transform transition ease-in duration-150"
                                leaveFrom="translate-x-0"
                                leaveTo="translate-x-full"
                            >
                                <Dialog.Panel className={cn('pointer-events-auto w-screen', width)}>
                                    <div className="flex h-full flex-col bg-white shadow-xl">
                                        <div className="flex items-center justify-between border-b border-surface-border px-5 py-4">
                                            <Dialog.Title className="font-display text-lg font-semibold text-ink">{title}</Dialog.Title>
                                            <button type="button" onClick={onClose} className="rounded-lg p-1.5 text-ink-muted hover:bg-surface hover:text-ink">
                                                <X className="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div className="flex-1 overflow-y-auto px-5 py-4">{children}</div>
                                        {footer && <div className="border-t border-surface-border px-5 py-4">{footer}</div>}
                                    </div>
                                </Dialog.Panel>
                            </Transition.Child>
                        </div>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
