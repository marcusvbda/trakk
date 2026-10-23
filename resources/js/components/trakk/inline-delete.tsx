import { useState } from 'react';
import { TextField } from '@/components/trakk/text-field';
import { TrakkButton } from '@/components/ui/trakk-button';

type InlineDeleteProps = {
    id: string;
    label: string;
    onConfirm: () => void;
    processing: boolean;
    confirmPhrase?: string;
};

export function InlineDelete({
    id,
    label,
    onConfirm,
    processing,
    confirmPhrase,
}: InlineDeleteProps) {
    const [isConfirming, setIsConfirming] = useState(false);
    const [typed, setTyped] = useState('');
    const canDelete = confirmPhrase === undefined || typed === confirmPhrase;

    if (!isConfirming) {
        return (
            <button
                type="button"
                onClick={() => setIsConfirming(true)}
                className="self-start font-mono text-xs text-danger hover:underline"
            >
                {label}
            </button>
        );
    }

    return (
        <div className="flex flex-col gap-2.5 rounded-control border border-danger-line p-3">
            {confirmPhrase !== undefined && (
                <TextField
                    id={id}
                    label="type the title to delete"
                    placeholder={confirmPhrase}
                    value={typed}
                    onChange={(event) => setTyped(event.target.value)}
                />
            )}
            <div className="flex gap-2">
                <TrakkButton onClick={() => setIsConfirming(false)}>
                    keep
                </TrakkButton>
                <TrakkButton
                    disabled={!canDelete || processing}
                    aria-busy={processing}
                    onClick={onConfirm}
                    className="border-danger-line text-danger disabled:opacity-40"
                >
                    {label}
                </TrakkButton>
            </div>
        </div>
    );
}
