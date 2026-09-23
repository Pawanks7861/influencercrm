import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/format';

export default function ActionMenu({ items = [], align = 'right' }) {
    const visible = items.filter(Boolean);

    if (!visible.length) return null;

    return (
        <Menu as="div" className="relative inline-block text-left">
            <Menu.Button className="rounded-lg p-1.5 text-ink-muted hover:bg-surface hover:text-ink">
                <MoreHorizontal className="h-4 w-4" />
            </Menu.Button>
            <Transition
                as={Fragment}
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items
                    className={cn(
                        'absolute z-20 mt-1 w-48 origin-top-right rounded-lg border border-surface-border bg-white p-1 shadow-soft focus:outline-none',
                        align === 'right' ? 'right-0' : 'left-0'
                    )}
                >
                    {visible.map((item) => (
                        <Menu.Item key={item.label} disabled={item.disabled}>
                            {({ active }) => (
                                <button
                                    type="button"
                                    onClick={item.onClick}
                                    disabled={item.disabled}
                                    className={cn(
                                        'flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm',
                                        item.danger ? 'text-red-600' : 'text-ink-soft',
                                        active && (item.danger ? 'bg-red-50' : 'bg-surface'),
                                        item.disabled && 'opacity-40'
                                    )}
                                >
                                    {item.icon && <item.icon className="h-3.5 w-3.5" />}
                                    {item.label}
                                </button>
                            )}
                        </Menu.Item>
                    ))}
                </Menu.Items>
            </Transition>
        </Menu>
    );
}
