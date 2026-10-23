import { createContext, useContext } from 'react';

export type CommandPaletteApi = {
    open: () => void;
};

export const CommandPaletteContext = createContext<CommandPaletteApi | null>(
    null,
);

export function useCommandPalette(): CommandPaletteApi {
    const palette = useContext(CommandPaletteContext);

    if (!palette) {
        throw new Error(
            'useCommandPalette needs the CommandPalette from TrakkLayout.',
        );
    }

    return palette;
}
