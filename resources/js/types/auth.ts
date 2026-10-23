export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    timezone: string;
    daily_points_target: number;
    telegram_chat_id: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
