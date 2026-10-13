<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu, Moon, Sun } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import WaveDivider from '@/components/site/WaveDivider.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAppearance } from '@/composables/useAppearance';

const page = usePage();
const site = computed(() => page.props.site);
const menuOpen = ref(false);
const { resolvedAppearance, updateAppearance } = useAppearance();

const isCurrent = (href: string) =>
    page.url === href || page.url.startsWith(`${href}/`);

// The sticky "Come and try it" bar is for phones, and is left off the pages
// that are already the next step.
const showStickyCta = computed(
    () => !/^\/(contact|join|paddle\/beginners)/.test(page.url),
);

// The server prints the title, description and canonical link into the HTML
// (see App\Support\Seo). This keeps them right when moving between pages
// without a full reload.
function syncHead() {
    const meta = page.props.meta;

    if (!meta) {
        return;
    }

    document.title = meta.title;
    document
        .querySelector('meta[name="description"]')
        ?.setAttribute('content', meta.description);
    document
        .querySelector('link[rel="canonical"]')
        ?.setAttribute('href', meta.canonical);
}

let stopListening: (() => void) | undefined;

onMounted(() => {
    stopListening = router.on('navigate', () => {
        menuOpen.value = false;
        syncHead();
    });
});

onUnmounted(() => stopListening?.());
</script>

<template>
    <div class="site flex min-h-screen flex-col">
        <a
            href="#content"
            class="sr-only rounded-full bg-gold-400 px-5 py-3 font-semibold text-ink-950 focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50"
            >Skip to content</a
        >

        <header class="bg-ink-900 text-white">
            <div class="shell flex items-center justify-between gap-4 py-3">
                <Link
                    href="/"
                    class="flex min-h-11 items-center gap-3"
                    :aria-label="`${site.name}: home`"
                >
                    <img
                        src="/images/brand/logo.png"
                        alt=""
                        width="400"
                        height="293"
                        class="h-11 w-auto rounded-lg sm:h-12"
                    />
                    <span
                        class="font-display text-lg leading-tight font-bold sm:text-xl"
                    >
                        <span class="lg:hidden xl:inline"
                            >Blackwater Valley</span
                        >
                        <span class="hidden lg:inline xl:hidden">{{
                            site.shortName
                        }}</span>
                        <span
                            class="block text-sm font-semibold text-gold-400 lg:hidden xl:block"
                            >Canoe Club</span
                        >
                    </span>
                </Link>

                <nav aria-label="Main" class="hidden lg:block">
                    <ul class="flex items-center gap-1">
                        <li v-for="item in site.nav" :key="item.href">
                            <Link
                                :href="item.href"
                                class="flex min-h-11 items-center rounded-full px-3 font-semibold whitespace-nowrap transition-colors hover:bg-white/10"
                                :class="{
                                    'bg-white/10 text-gold-400': isCurrent(
                                        item.href,
                                    ),
                                }"
                                :aria-current="
                                    isCurrent(item.href) ? 'page' : undefined
                                "
                                >{{ item.label }}</Link
                            >
                        </li>
                    </ul>
                </nav>

                <Link
                    href="/paddle/beginners"
                    class="btn btn-gold hidden whitespace-nowrap lg:inline-flex"
                    >Come and try it</Link
                >

                <Sheet v-model:open="menuOpen">
                    <SheetTrigger as-child>
                        <button
                            type="button"
                            class="flex size-11 items-center justify-center rounded-full hover:bg-white/10 lg:hidden"
                        >
                            <Menu class="size-6" aria-hidden="true" />
                            <span class="sr-only">Menu</span>
                        </button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        class="site w-[min(22rem,90vw)] border-ink-700 bg-ink-900 p-6 text-white"
                    >
                        <SheetTitle class="font-display text-xl text-white"
                            >Menu</SheetTitle
                        >
                        <SheetDescription class="sr-only"
                            >Site navigation</SheetDescription
                        >
                        <nav aria-label="Main" class="mt-4">
                            <ul class="divide-y divide-white/10">
                                <li v-for="item in site.nav" :key="item.href">
                                    <Link
                                        :href="item.href"
                                        class="flex min-h-14 items-center font-display text-2xl font-bold"
                                        :class="{
                                            'text-gold-400': isCurrent(
                                                item.href,
                                            ),
                                        }"
                                        :aria-current="
                                            isCurrent(item.href)
                                                ? 'page'
                                                : undefined
                                        "
                                        >{{ item.label }}</Link
                                    >
                                </li>
                            </ul>
                        </nav>
                        <Link
                            href="/paddle/beginners"
                            class="btn btn-gold mt-6 w-full"
                            >Come and try it</Link
                        >
                    </SheetContent>
                </Sheet>
            </div>
        </header>

        <main id="content" class="flex-1">
            <slot />
        </main>

        <footer
            class="relative bg-ink-950 text-river-100"
            :class="{ 'pb-20 lg:pb-0': showStickyCta }"
        >
            <WaveDivider flip class="absolute inset-x-0 -top-px text-page" />
            <div
                class="shell grid gap-10 pt-20 pb-10 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div>
                    <img
                        src="/images/brand/logo.png"
                        alt=""
                        width="400"
                        height="293"
                        loading="lazy"
                        class="h-16 w-auto rounded-xl"
                    />
                    <p class="mt-3 font-display text-lg font-bold text-white">
                        {{ site.name }}
                    </p>
                    <p class="mt-1">{{ site.tagline }}.</p>
                    <p class="mt-3 text-sm">
                        Paddling since {{ site.founded }}. Affiliated to
                        <a
                            :href="site.governingBody.url"
                            class="underline underline-offset-4"
                            rel="noopener"
                            >{{ site.governingBody.name }}</a
                        >.
                    </p>
                </div>

                <nav
                    v-for="(links, heading) in site.footer"
                    :key="heading"
                    :aria-label="String(heading)"
                >
                    <h2
                        class="font-sans text-sm font-semibold tracking-widest text-gold-400 uppercase"
                    >
                        {{ heading }}
                    </h2>
                    <ul class="mt-3">
                        <li v-for="link in links" :key="link.href">
                            <Link
                                :href="link.href"
                                class="flex min-h-10 items-center underline-offset-4 hover:text-white hover:underline"
                                >{{ link.label }}</Link
                            >
                        </li>
                    </ul>
                </nav>

                <div>
                    <h2
                        class="font-sans text-sm font-semibold tracking-widest text-gold-400 uppercase"
                    >
                        Get in touch
                    </h2>
                    <ul class="mt-3">
                        <li>
                            <a
                                :href="`mailto:${site.email}`"
                                class="flex min-h-10 items-center break-all underline-offset-4 hover:text-white hover:underline"
                                >{{ site.email }}</a
                            >
                        </li>
                        <li v-if="site.social.facebook">
                            <a
                                :href="site.social.facebook"
                                rel="noopener"
                                class="flex min-h-10 items-center underline-offset-4 hover:text-white hover:underline"
                                >Facebook page</a
                            >
                        </li>
                        <li v-if="site.social.facebook_group">
                            <a
                                :href="site.social.facebook_group"
                                rel="noopener"
                                class="flex min-h-10 items-center underline-offset-4 hover:text-white hover:underline"
                                >Facebook group</a
                            >
                        </li>
                        <li v-if="site.social.vimeo">
                            <a
                                :href="site.social.vimeo"
                                rel="noopener"
                                class="flex min-h-10 items-center underline-offset-4 hover:text-white hover:underline"
                                >Videos on Vimeo</a
                            >
                        </li>
                    </ul>
                    <img
                        src="/images/brand/clubmark.png"
                        alt="Clubmark accredited club"
                        width="240"
                        height="167"
                        loading="lazy"
                        class="mt-4 h-14 w-auto"
                    />
                </div>
            </div>
            <div class="border-t border-white/10">
                <div
                    class="shell flex flex-wrap items-center justify-between gap-3 py-4 text-sm"
                >
                    <p>
                        © {{ new Date().getFullYear() }} {{ site.name }}. Run by
                        volunteers.
                    </p>
                    <button
                        type="button"
                        class="flex min-h-11 items-center gap-2 rounded-full px-3 hover:bg-white/10"
                        @click="
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        "
                    >
                        <Sun
                            v-if="resolvedAppearance === 'dark'"
                            class="size-4"
                            aria-hidden="true"
                        />
                        <Moon v-else class="size-4" aria-hidden="true" />
                        {{
                            resolvedAppearance === 'dark'
                                ? 'Light mode'
                                : 'Dark mode'
                        }}
                    </button>
                </div>
            </div>
        </footer>

        <!-- Phones only: the next step is always within reach of a thumb. -->
        <div
            v-if="showStickyCta"
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink-900/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur lg:hidden"
        >
            <Link href="/paddle/beginners" class="btn btn-gold w-full"
                >Come and try it</Link
            >
        </div>
    </div>
</template>
