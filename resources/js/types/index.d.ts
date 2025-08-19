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
    name: string | null;
    designation: string | null;
    bio: string | null;
    street: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    profile_photo_url?: string | null;
    public_url?: string | null;
    share_profile?: boolean;
    links?: ProjectLink[];
    documents?: ProjectAsset[];
}

export interface Category {
    id?: number;
    name: string;
    key: string;
    user_id?: number | null;
}

export interface Status {
    name: string;
    key: string;
    is_active?: boolean;
}

export interface ProjectLink {
    title: string;
    url: string;
    type?: string;
}

export interface ProjectAsset {
    id?: number;
    name?: string;
    path?: string;
    size?: number;
    type?: string;
    url?: string;
    filename?: string; // MinIO URL
    display_name?: string; // Original filename
    asset_type?: {
        key: string;
        name: string;
    };
}

export interface ProjectSettings {
    showDescription?: boolean;
    showCategory?: boolean;
    showStatus?: boolean;
    showDates?: boolean;
    showTags?: boolean;
    showTechnologies?: boolean;
    showLinks?: boolean;
    showAssets?: boolean;
}

export interface Project {
    id?: number;
    name: string;
    description?: string;
    category?: string;
    status?: string;
    start_date?: string;
    end_date?: string;
    is_public?: boolean;
    tags?: string[];
    technologies?: string[];
    links?: ProjectLink[];
    assets?: ProjectAsset[];
    settings?: ProjectSettings;
    created_at?: string;
    updated_at?: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
