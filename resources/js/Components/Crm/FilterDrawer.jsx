import Drawer from './Drawer';

export default function FilterDrawer({ open, onClose, title = 'Filters', children, onApply, onReset }) {
    return (
        <Drawer
            open={open}
            onClose={onClose}
            title={title}
            footer={
                <div className="flex items-center justify-between gap-2">
                    <button type="button" className="crm-btn-ghost" onClick={onReset}>
                        Reset
                    </button>
                    <button type="button" className="crm-btn-primary" onClick={onApply}>
                        Apply filters
                    </button>
                </div>
            }
        >
            <div className="space-y-4">{children}</div>
        </Drawer>
    );
}
