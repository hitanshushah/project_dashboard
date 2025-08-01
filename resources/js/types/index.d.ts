import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    profile?: Profile;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    categories?: Category[];
    statuses?: Status[];
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    username: string | null;
    created_at: string;
    updated_at: string;
}

export interface Profile {
    id: number;
    name: string;
    bio: string | null;
}

export interface Category {
    name: string;
    key: string;
}

export interface Status {
    name: string;
    key: string;
    is_active?: boolean;
}

export interface ProjectLink {
    title: string;
    url: string;
}

export interface ProjectAsset {
    name?: string;
    path?: string;
    size?: number;
    type?: string;
}

export interface Project {
    id?: number;
    name: string;
    description?: string;
    category?: string;
    status?: string;
    start_date?: string;
    end_date?: string;
    tags?: string[];
    technologies?: string[];
    links?: ProjectLink[];
    notes?: string;
    assets?: ProjectAsset[];
    created_at?: string;
    updated_at?: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
