export type SiteImage = { src: string; srcset: string; alt: string } | null;

export type NavLink = { label: string; href: string };

export type Site = {
    name: string;
    shortName: string;
    tagline: string;
    email: string;
    founded: number;
    governingBody: { name: string; url: string };
    social: Record<string, string>;
    nav: NavLink[];
    footer: Record<string, NavLink[]>;
};

export type PageMeta = {
    title: string;
    description: string;
    canonical: string;
    breadcrumbs: NavLink[];
};

export type Fact = { label: string; value: string };

export type FaqItem = { question: string; html: string };

export type EventSummary = {
    title: string;
    url: string;
    summary: string | null;
    starts_at: string;
    day: string;
    month: string;
    month_key: string;
    month_label: string;
    date_key: string;
    weekday: string;
    date_label: string;
    time_label: string;
    venue: { name: string; url: string } | null;
    category: { name: string; slug: string; colour: string };
    discipline: string | null;
    grade: number | null;
    suitability: string | null;
    cancelled: boolean;
    price_label: string | null;
    places_left: number | null;
    is_full: boolean;
    image: SiteImage;
};

export type DisciplineSummary = {
    name: string;
    slug: string;
    url: string;
    summary: string | null;
    image: SiteImage;
};

export type ArticleSummary = {
    title: string;
    url: string;
    excerpt: string;
    date: string | null;
    datetime: string | null;
    category: string | null;
    image: SiteImage;
};

export type VenueSummary = {
    name: string;
    url: string;
    summary: string | null;
    address: string;
    map_url: string;
    latitude: number | null;
    longitude: number | null;
    image: SiteImage;
};

export type ContentPage = {
    title: string;
    summary: string | null;
    facts: Fact[];
    html: string;
    reviewed: string | null;
    image: SiteImage;
    markdown_url: string;
};

export type PageLink = { title: string; summary?: string | null; url: string };
