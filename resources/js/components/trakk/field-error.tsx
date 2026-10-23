export function FieldError({ id, message }: { id: string; message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p id={id} role="alert" className="font-mono text-sm text-danger">
            {message}
        </p>
    );
}
