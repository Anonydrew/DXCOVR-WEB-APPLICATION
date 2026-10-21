@props(['menus' => [], 'site'])
@php
    $socials = collect($site->get('social.links', []))->filter(fn ($link) => filled($link['url'] ?? null));
    $email = $site->get('contact.email');
    $phone = $site->get('contact.phone');
    $whatsapp = $site->whatsappUrl();
    $columns = ['explore' => 'Explore', 'company' => 'Company', 'legal' => 'Legal'];
@endphp
<footer class="surface-dark relative overflow-hidden" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Site footer</h2>
    <div class="container-dx relative pt-20 pb-10 sm:pt-28">
        <div class="grid gap-12 pb-16 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <p class="eyebrow accent">{{ $site->get('site.tagline') }}</p>
                <p class="display-md mt-6 max-w-2xl">{{ $site->get('footer.cta_heading') }}</p>
                @if ($site->get('footer.cta_url'))
                    <a href="{{ $site->get('footer.cta_url') }}" class="btn btn-primary mt-8">
                        {{ $site->get('footer.cta_label', 'Start a project') }}
                        <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                    </a>
                @endif
            </div>
            <div class="lg:col-span-5 lg:pl-10">
                <p class="text-muted">{{ $site->get('footer.statement') }}</p>
                <dl class="mt-8 space-y-4 text-sm">
                    @if ($email)
                        <div class="flex items-start gap-3">
                            <dt class="text-subtle"><x-icon name="mail" :size="18" /><span class="sr-only">Email</span></dt>
                            <dd><a href="mailto:{{ $email }}" class="link-underline">{{ $email }}</a></dd>
                        </div>
                    @endif
                    @if ($phone)
                        <div class="flex items-start gap-3">
                            <dt class="text-subtle"><x-icon name="phone" :size="18" /><span class="sr-only">Phone</span></dt>
                            <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="link-underline">{{ $phone }}</a></dd>
                        </div>
                    @endif
                    @if ($whatsapp)
                        <div class="flex items-start gap-3">
                            <dt class="text-subtle"><x-icon name="whatsapp" :size="18" /><span class="sr-only">WhatsApp</span></dt>
                            <dd><a href="{{ $whatsapp }}" class="link-underline" target="_blank" rel="noopener">Chat on WhatsApp</a></dd>
                        </div>
                    @endif
                    <div class="flex items-start gap-3">
                        <dt class="text-subtle"><x-icon name="map-pin" :size="18" /><span class="sr-only">Location</span></dt>
                        <dd class="text-muted">{{ $site->get('contact.address') ?: $site->get('contact.location_summary') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-12">
            <div class="sm:col-span-2 lg:col-span-4">
                <x-site.logo :href="route('home')" :label="$site->get('site.name')" />
                @if ($socials->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap gap-2" aria-label="Social media">
                        @foreach ($socials as $social)
                            <li>
                                <a href="{{ $social['url'] }}" class="grid size-11 place-items-center rounded-full border border-line-strong text-muted transition hover:border-text hover:text-text" target="_blank" rel="noopener me" aria-label="{{ $social['label'] ?? \Illuminate\Support\Str::headline($social['network'] ?? 'Link') }}">
                                    <x-icon :name="$social['network'] ?? 'link'" :size="18" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            @foreach ($columns as $key => $title)
                @if (! empty($menus[$key]))
                    <nav class="{{ $key === 'legal' ? 'lg:col-span-2' : 'lg:col-span-3' }}" aria-label="{{ $title }} links">
                        <h3 class="mono-label text-subtle">{{ $title }}</h3>
                        <ul class="mt-5 space-y-3">
                            @foreach ($menus[$key] as $item)
                                <li>
                                    <a href="{{ $item['href'] }}" class="text-muted transition hover:text-text" @if ($item['new_tab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            @endforeach
        </div>

        <div class="flex flex-col gap-4 pt-8 text-sm text-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $site->get('footer.copyright') }}</p>
            <p class="mono-label">Based in Ghana · Working beyond borders</p>
        </div>
    </div>
</footer>
