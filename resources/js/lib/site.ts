/**
 * Event category colour names (chosen in the admin) as colours that give
 * white text enough contrast.
 */
const COLOURS: Record<string, string> = {
    teal: '#17676c',
    blue: '#1d4ed8',
    gold: '#8a6a00',
    coral: '#be123c',
    green: '#15803d',
    violet: '#6d28d9',
    slate: '#475569',
};

export function categoryColour(name: string): string {
    return COLOURS[name] ?? COLOURS.slate;
}
