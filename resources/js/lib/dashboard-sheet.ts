export type DashboardSheet = 'replan' | 'check-in';

export const dashboardSheetEvent = 'trakk:dashboard-sheet';

const dashboardSheets: DashboardSheet[] = ['replan', 'check-in'];

/** The sheet asked for through `/?sheet=check-in`, which is how other pages reach the dashboard's sheets. */
export function requestedDashboardSheet(url: string): DashboardSheet | null {
    const requested = new URL(url, 'http://trakk.local').searchParams.get(
        'sheet',
    );

    return dashboardSheets.find((sheet) => sheet === requested) ?? null;
}
