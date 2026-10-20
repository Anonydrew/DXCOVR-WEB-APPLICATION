<section class="surface-dark section" aria-labelledby="faq-{{ $section->id }}">
    <div class="container-dx grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <x-site.section-heading :id="'faq-'.$section->id" :eyebrow="$section->value('eyebrow')" :heading="$section->value('heading') ?: 'Questions, answered'" class="!block" />
        </div>
        <div class="lg:col-span-8">
            <x-site.faq-list :faqs="$data['faqs']" />
        </div>
    </div>
</section>
