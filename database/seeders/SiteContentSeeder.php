<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\PublicationStatus;
use App\Models\ArticleCategory;
use App\Models\Department;
use App\Models\Faq;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\TeamMember;
use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Installs DXCOVR's starter structure and copy.
 *
 * Safe to run in production: every record is created only when missing and
 * existing (edited) content is never overwritten. It contains no
 * testimonials, client names, statistics or case studies. Legal pages are
 * created as drafts because they must be reviewed before publication.
 */
class SiteContentSeeder extends Seeder
{
    /**
     * Short navigation labels keyed by service slug.
     *
     * @var array<string, string>
     */
    protected array $navLabels = [];

    public function run(): void
    {
        $this->taxonomies();
        $services = $this->services();
        $this->product($services);
        $this->founder();
        $this->faqs();
        $this->pages();
        $this->legalPages();
        $this->navigation($services);

        ContentCache::flush();
    }

    protected function taxonomies(): void
    {
        foreach ([
            'Software and SaaS', 'ERP and management systems', 'Web development', 'AI and automation',
            'Hardware and IoT', 'AR, VR and WebXR', '3D modelling', 'UI/UX and branding',
        ] as $index => $name) {
            ProjectCategory::query()->firstOrCreate(['name' => $name], ['sort_order' => $index]);
        }

        foreach ([
            'Software engineering', 'Artificial intelligence', 'Digital transformation', 'Hardware and IoT',
            'Immersive technologies', 'AR, VR and WebXR', '3D design', 'UI/UX', 'Company news',
            'Product updates', 'Tutorials and technical insights',
        ] as $index => $name) {
            ArticleCategory::query()->firstOrCreate(['name' => $name], ['sort_order' => $index]);
        }

        foreach (['Engineering', 'Design', 'Immersive & 3D', 'Operations'] as $index => $name) {
            Department::query()->firstOrCreate(['name' => $name], ['sort_order' => $index]);
        }
    }

    /**
     * @return array<string, Service>
     */
    protected function services(): array
    {
        $steps = fn (array $items) => array_map(fn (array $item) => ['title' => $item[0], 'body' => $item[1]], $items);

        $definitions = [
            'software-engineering-and-business-systems' => [
                'title' => 'Software Engineering and Business Systems',
                'nav' => 'Software & business systems',
                'icon' => 'software',
                'short_description' => 'Custom software, ERP systems, SaaS platforms and integrations built around how your team works.',
                'body' => '<p>Many teams run on spreadsheets and tools that do not connect. We build one reliable system for your work, with the right access, records and reports.</p><h2>Easy to maintain</h2><p>We use proven tools, clear structure and good documentation, so your system is secure and easy to grow.</p><h2>Start small or go big</h2><p>We can automate one process or build a full platform. Either way, we start with how you work today.</p>',
                'capabilities' => ['Custom applications', 'ERP and management systems', 'SaaS platforms', 'API development', 'Business process automation', 'System integrations', 'Workflow engineering'],
                'use_cases' => $steps([
                    ['Replace spreadsheets', 'Move key work into a secure app with roles, history and reports.'],
                    ['Connect your tools', 'Link payments, accounting, messaging and databases through reliable APIs.'],
                    ['Launch a software product', 'Plan and build a product you can offer to your own customers.'],
                ]),
                'process_steps' => $steps([
                    ['Discover', 'Map how work happens today and what matters most.'],
                    ['Design', 'Agree on scope and test ideas with clickable prototypes.'],
                    ['Build', 'Develop in short cycles with regular demos and tests.'],
                    ['Launch and support', 'Go live smoothly, train your team and keep improving.'],
                ]),
            ],
            'artificial-intelligence-and-automation' => [
                'title' => 'Artificial Intelligence and Automation',
                'nav' => 'AI & automation',
                'icon' => 'ai',
                'short_description' => 'Practical AI: assistants, document processing and automation, built around real work.',
                'body' => '<p>AI works best on a clear, specific problem. We find where it can save time or improve decisions, then build tools that fit into your existing work.</p><h2>Based on your information</h2><p>Assistants can answer questions from your own documents and show their sources, so answers can be checked.</p><h2>Used responsibly</h2><p>We test models before launch, keep people in charge of important decisions and protect your data. If simple automation does the job better, we will tell you.</p>',
                'capabilities' => ['AI-powered applications', 'AI assistants and conversational interfaces', 'Intelligent document processing', 'AI integrations', 'Retrieval-augmented generation (RAG)', 'Predictive analytics and machine learning', 'Intelligent business automation'],
                'use_cases' => $steps([
                    ['Answers from your documents', 'An assistant that searches your policies and manuals and shows where each answer came from.'],
                    ['Data from documents', 'Turn forms, invoices and reports into data your systems can use.'],
                    ['Sort incoming requests', 'Send each request to the right person with the context they need.'],
                ]),
                'process_steps' => $steps([
                    ['Assess', 'Check that AI fits the problem and the data is there.'],
                    ['Prototype', 'Build a small version and test it on real examples.'],
                    ['Integrate', 'Connect it to your systems with safeguards and human review.'],
                    ['Monitor', 'Track quality and improve it over time.'],
                ]),
            ],
            'hardware-programming-and-embedded-systems' => [
                'title' => 'Hardware Programming and Embedded Systems',
                'nav' => 'Hardware & embedded',
                'icon' => 'hardware',
                'short_description' => 'Firmware, sensors and connected devices, plus the software that brings device data into your systems.',
                'body' => '<p>Connected devices only help when hardware, firmware and software work together. We program microcontrollers, connect sensors and send device data safely to your dashboards and systems.</p><h2>Prototype first</h2><p>Hardware changes are costly. We build small prototypes to test power, connectivity and accuracy before committing to a design.</p>',
                'capabilities' => ['Microcontroller programming', 'Embedded software', 'IoT device integration', 'Sensor and actuator integration', 'Hardware–software communication', 'Connected-device prototypes', 'Device automation'],
                'use_cases' => $steps([
                    ['Sensor monitoring', 'Track temperature, humidity or energy use in real time.'],
                    ['Prototype a connected product', 'Prove a device idea with working hardware and an app.'],
                    ['Automate equipment', 'Control motors and switches by rule, schedule or remote app.'],
                ]),
                'process_steps' => $steps([
                    ['Define', 'Agree what to measure or control, and where.'],
                    ['Prototype', 'Build and test a working proof of concept.'],
                    ['Integrate', 'Connect devices to software and dashboards securely.'],
                    ['Refine', 'Strengthen the firmware and plan updates and upkeep.'],
                ]),
            ],
            'augmented-reality-and-virtual-reality' => [
                'title' => 'Augmented Reality and Virtual Reality',
                'nav' => 'AR & VR',
                'icon' => 'xr',
                'short_description' => 'AR and VR experiences for training, visualisation and exploration. Comfortable, useful and easy to use.',
                'body' => '<p>Immersive technology shines when it helps people see or practise something that is hard to do any other way. We build AR and VR experiences with a clear purpose and simple controls.</p><h2>Made for real people</h2><p>We test early with real users and plan for the devices your audience has.</p>',
                'capabilities' => ['AR product visualisation', 'VR environments', 'Interactive simulations', 'Virtual tours', 'Immersive learning', 'Training experiences', 'Spatial interactions'],
                'use_cases' => $steps([
                    ['See products in place', 'Let customers place a true-to-size product in their own space.'],
                    ['Safe training', 'Practise tasks in VR without risk to people or equipment.'],
                    ['Remote tours', 'Offer guided virtual tours of sites and properties.'],
                ]),
                'process_steps' => $steps([
                    ['Concept', 'Define the purpose, audience and devices.'],
                    ['Storyboard', 'Plan the journey and interactions.'],
                    ['Develop', 'Build it with 3D assets, logic and sound.'],
                    ['Test and release', 'Test with users, tune performance and publish.'],
                ]),
            ],
            'webxr-and-immersive-web' => [
                'title' => 'WebXR and Immersive Web',
                'nav' => 'WebXR & immersive web',
                'icon' => 'webxr',
                'short_description' => '3D, AR and VR experiences that run in the browser. No app to install, just a link.',
                'body' => '<p>WebXR brings 3D, AR and VR to the web. People open it from a link on a phone, laptop or headset, with nothing to install.</p><h2>Fast on everyday devices</h2><p>We optimise models and code so experiences load quickly and run smoothly, with fallbacks where needed.</p>',
                'capabilities' => ['Browser-based 3D experiences', 'Web-based AR and VR', 'Virtual showrooms', 'Interactive product configurators', 'Immersive education', 'Spatial interfaces', 'Real-time 3D in the browser'],
                'use_cases' => $steps([
                    ['Virtual showrooms', 'Show products in a 3D space that works in any modern browser.'],
                    ['Product configurators', 'Let customers change colours and options and see the result instantly.'],
                    ['Interactive learning', 'Explain complex things with 3D models people can explore.'],
                ]),
                'process_steps' => $steps([
                    ['Plan', 'Agree on the experience, devices and speed targets.'],
                    ['Create', 'Make or optimise 3D assets for real-time use.'],
                    ['Build', 'Develop the experience and its fallbacks.'],
                    ['Optimise', 'Tune loading, performance and accessibility.'],
                ]),
            ],
            '3d-modelling-and-visualisation' => [
                'title' => '3D Modelling and Visualisation',
                'nav' => '3D modelling & visualisation',
                'icon' => 'modelling',
                'short_description' => 'Product, hard-surface and environment models, materials and renders for marketing, AR, VR and the web.',
                'body' => '<p>Good 3D work balances accuracy, looks and performance. We create models and renders for marketing, and light real-time assets for AR, VR and WebXR.</p><h2>Ready to reuse</h2><p>You get organised files in the formats you need, ready for future projects.</p>',
                'capabilities' => ['Product modelling', 'Hard-surface modelling', 'Environment modelling', 'Texturing and materials', 'Lighting and rendering', '3D asset creation', 'Optimised assets for AR, VR and WebXR'],
                'use_cases' => $steps([
                    ['Product visuals', 'Realistic renders before, or instead of, a photo shoot.'],
                    ['Assets for immersive projects', 'Light, real-time models ready for AR, VR and the web.'],
                    ['Spaces and environments', 'Architectural scenes for presentations and walkthroughs.'],
                ]),
                'process_steps' => $steps([
                    ['Brief', 'Gather references, sizes and how the model will be used.'],
                    ['Model', 'Build the shapes with the right level of detail.'],
                    ['Look development', 'Set up materials, lighting and cameras.'],
                    ['Deliver', 'Render, optimise and export in your formats.'],
                ]),
            ],
            'web-development-ui-ux-and-digital-design' => [
                'title' => 'Web Development, UI/UX and Digital Design',
                'nav' => 'Web, UI/UX & design',
                'icon' => 'design',
                'short_description' => 'Websites, web apps and digital products, from research and design to branding, launch and support.',
                'body' => '<p>We design and build websites and web apps that are fast, accessible and easy to manage. Designers and engineers work together, so what we design is what we build.</p><h2>Clear design</h2><p>From research to interface design and prototypes, we help people find what they need and get things done.</p><h2>Brand and support</h2><p>We also create brand identities and look after hosting, updates and IT support after launch.</p>',
                'capabilities' => ['Corporate websites', 'E-commerce platforms', 'Web applications', 'User experience design', 'Interface design and prototyping', 'Design systems', 'Brand identity and graphic design', 'Deployment, IT support and maintenance'],
                'use_cases' => $steps([
                    ['A website you can manage', 'A fast, accessible site your team can update with ease.'],
                    ['Sell online', 'Clear product pages, simple checkout and reliable operations.'],
                    ['A consistent brand', 'An identity and design system that keeps everything recognisably yours.'],
                ]),
                'process_steps' => $steps([
                    ['Research', 'Understand your audience, goals and content.'],
                    ['Design', 'Shape the structure, interface and look.'],
                    ['Build', 'Develop and test on every device.'],
                    ['Launch and care', 'Go live, monitor and maintain.'],
                ]),
            ],
        ];

        $services = [];
        $index = 0;

        foreach ($definitions as $slug => $definition) {
            $service = Service::query()->where('slug', $slug)->first();

            if (! $service) {
                $service = new Service;
                $service->fill([
                    'title' => $definition['title'],
                    'slug' => $slug,
                    'icon' => $definition['icon'],
                    'short_description' => $definition['short_description'],
                    'body' => $definition['body'],
                    'capabilities' => $definition['capabilities'],
                    'use_cases' => $definition['use_cases'],
                    'process_steps' => $definition['process_steps'],
                    'is_featured' => $index < 3,
                    'sort_order' => $index,
                ]);
                $this->markPublished($service);
            }

            $this->navLabels[$slug] = $definition['nav'];
            $services[$slug] = $service;
            $index++;
        }

        return $services;
    }

    /**
     * @param  array<string, Service>  $services
     */
    protected function product(array $services): void
    {
        if (Product::query()->where('slug', 'dxcovr-clinic-erp')->exists()) {
            return;
        }

        $product = new Product;
        $product->fill([
            'name' => 'DXCOVR Clinic ERP',
            'slug' => 'dxcovr-clinic-erp',
            'tagline' => 'Clinic operations in one connected system',
            'short_description' => 'An ERP system by DXCOVR that helps clinics run day-to-day operations in one secure place.',
            'body' => '<p>DXCOVR Clinic ERP is our own product for clinics. It keeps the information your team relies on in one place, so less time goes on paperwork and more on patients.</p><p>Details on modules and availability are coming soon. To see if it fits your clinic, request a demo or ask us a question.</p>',
            'product_status' => ProductStatus::InDevelopment,
            'demo_requests_enabled' => true,
            'enquiries_enabled' => true,
            'pricing_visible' => false,
            'is_featured' => true,
            'sort_order' => 0,
        ]);
        $this->markPublished($product);

        $product->services()->sync(array_filter([$services['software-engineering-and-business-systems']->id ?? null]));
    }

    protected function founder(): void
    {
        if (TeamMember::query()->where('is_founder', true)->exists()) {
            return;
        }

        $founder = new TeamMember;
        $founder->fill([
            'name' => 'Anthony Jnr Ahiable',
            'slug' => 'anthony-jnr-ahiable',
            'role_title' => 'Founder',
            'short_bio' => 'Anthony Jnr Ahiable founded DXCOVR to bring software, design, systems thinking and IT together.',
            'bio' => '<p>Anthony Jnr Ahiable is the founder of DXCOVR. His work spans software, digital design, systems thinking, IT and project delivery.</p><p>He started DXCOVR to bridge these fields, so every system, device and experience is planned as a whole, from first conversation to long-term support.</p>',
            'expertise' => ['Software', 'Digital design', 'Systems thinking', 'Information technology', 'Project execution'],
            'skills' => [],
            'experience' => [],
            'links' => [],
            'is_published' => true,
            'sort_order' => 0,
        ]);
        $founder->is_founder = true;
        $founder->save();
    }

    protected function faqs(): void
    {
        $faqs = [
            ['projects', 'Do you work with organisations outside Ghana?', '<p>Yes. We are based in Ghana and work with clients at home and abroad, remotely or in person.</p>'],
            ['projects', 'How does a project usually start?', '<p>With a conversation about your problem. If we are a good fit, we suggest an approach and next steps, often starting with a short discovery or prototype.</p>'],
            ['projects', 'Can you work with our existing systems?', '<p>Often, yes. We review what you have and advise whether to connect, extend or replace it.</p>'],
            ['careers', 'What happens after I apply?', '<p>You will get an email confirming we received your application. We review every application and will contact you about next steps.</p>'],
            ['careers', 'How do you handle my application data?', '<p>Your application and CV are stored privately and only our recruitment team can see them. We delete them after the period set out in our privacy policy.</p>'],
        ];

        foreach ($faqs as $index => [$group, $question, $answer]) {
            Faq::query()->firstOrCreate(['question' => $question], [
                'answer' => $answer,
                'group' => $group,
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }

    protected function pages(): void
    {
        $steps = [
            ['title' => 'Understand', 'body' => 'We start with the problem, the people and what success looks like, before choosing any technology.'],
            ['title' => 'Shape', 'body' => 'We prototype and agree on scope, so decisions are based on evidence.'],
            ['title' => 'Build', 'body' => 'We build in short cycles with regular demos and honest updates.'],
            ['title' => 'Support', 'body' => 'We launch carefully, document our work and keep improving it.'],
        ];

        $this->systemPage('home', 'DXCOVR', 'DXCOVR builds smart software, business systems and immersive digital experiences that help organisations work better.', [
            ['hero', [
                'eyebrow' => 'Technology. Thoughtfully Built.',
                'headline' => 'We turn complex ideas into digital solutions *that work.*',
                'subheadline' => 'We build smart software, business systems and immersive digital experiences that help organisations solve problems and grow.',
                'primary_label' => 'Explore Our Work',
                'primary_url' => '/work',
                'secondary_label' => 'Start a Project',
                'secondary_url' => '/contact',
                'media_id' => null,
                'show_ui_cards' => true,
                'proof_heading' => 'Inside DXCOVR',
                'proof_items' => [
                    ['value' => '7', 'label' => 'Service disciplines', 'title' => 'Our services', 'media_id' => null, 'body' => 'Business systems, AI, hardware, AR/VR, WebXR, 3D and design in one place.', 'url' => '/services'],
                    ['value' => '4', 'label' => 'Step process', 'title' => 'How we work', 'media_id' => null, 'body' => 'Understand, shape, build and support. One process for every project.', 'url' => '/about'],
                    ['value' => 'ERP', 'label' => 'Our own product', 'title' => 'DXCOVR Clinic ERP', 'media_id' => null, 'body' => 'Clinic operations in one connected system, built in-house.', 'url' => '/products/dxcovr-clinic-erp'],
                    ['value' => 'GH', 'label' => 'Based in Ghana', 'title' => 'Work with us', 'media_id' => null, 'body' => 'Working with organisations in Ghana and beyond.', 'url' => '/contact'],
                ],
                'logos' => [],
                'disciplines' => array_map(fn (string $label) => ['label' => $label], [
                    'Software engineering', 'Artificial intelligence', 'Embedded systems & IoT', 'Augmented reality',
                    'Virtual reality', 'WebXR', '3D visualisation', 'UI/UX & brand',
                ]),
            ]],
            ['spotlight', [
                'eyebrow' => null,
                'heading' => null,
                'items' => [
                    ['badge' => 'Our product', 'title' => 'DXCOVR Clinic ERP', 'body' => 'Clinic operations in one connected system, built by DXCOVR.', 'media_id' => null, 'icon' => 'software', 'tone' => 'dark', 'cta_label' => 'Learn more', 'cta_url' => '/products/dxcovr-clinic-erp'],
                    ['badge' => 'AI & automation', 'title' => 'AI that fits your workflow', 'body' => 'Assistants and document tools built on your own information, with people kept in control.', 'media_id' => null, 'icon' => 'ai', 'tone' => 'lime', 'cta_label' => 'Explore AI', 'cta_url' => '/services/artificial-intelligence-and-automation'],
                    ['badge' => 'Immersive', 'title' => '3D and XR in the browser', 'body' => 'AR, VR and WebXR for training and visualisation. No app to install.', 'media_id' => null, 'icon' => 'webxr', 'tone' => 'dark', 'cta_label' => 'Explore WebXR', 'cta_url' => '/services/webxr-and-immersive-web'],
                    ['badge' => 'Engineering', 'title' => 'Software and hardware, working as one', 'body' => 'From sensors to dashboards, connected systems built end to end.', 'media_id' => null, 'icon' => 'hardware', 'tone' => 'light', 'cta_label' => 'Explore embedded systems', 'cta_url' => '/services/hardware-programming-and-embedded-systems'],
                    ['badge' => '3D', 'title' => 'Models made for real time', 'body' => 'Product and environment models, optimised for AR, VR and the web.', 'media_id' => null, 'icon' => 'modelling', 'tone' => 'dark', 'cta_label' => 'Explore 3D', 'cta_url' => '/services/3d-modelling-and-visualisation'],
                    ['badge' => null, 'title' => 'Have an idea worth building?', 'body' => 'Tell us what you are working on. We will give you honest advice on next steps.', 'media_id' => null, 'icon' => 'design', 'tone' => 'lime', 'cta_label' => 'Start a project', 'cta_url' => '/contact'],
                ],
            ]],
            ['quick_links', [
                'heading' => null,
                'items' => [
                    ['icon' => 'layers', 'label' => 'Explore services', 'url' => '/services'],
                    ['icon' => 'briefcase', 'label' => 'See our work', 'url' => '/work'],
                    ['icon' => 'box', 'label' => 'DXCOVR products', 'url' => '/products'],
                    ['icon' => 'file', 'label' => 'Read insights', 'url' => '/insights'],
                    ['icon' => 'users', 'label' => 'Join the team', 'url' => '/careers'],
                    ['icon' => 'mail', 'label' => 'Contact us', 'url' => '/contact'],
                ],
            ]],
            ['capabilities', [
                'eyebrow' => 'Capabilities',
                'heading' => 'Seven disciplines. One connected practice.',
                'intro' => 'Real problems cross software, hardware and design. So do we.',
                'limit' => 7,
                'cta_label' => 'All services',
                'cta_url' => '/services',
            ]],
            ['statement', [
                'eyebrow' => 'What we do',
                'statement' => 'DXCOVR builds smart software, connected systems and immersive experiences, bringing together *engineering, design and new technology* to solve real problems.',
                'body' => "We cover the whole product: logic, data, devices, interface and experience. Fewer hand-offs means solutions that work for real users.\n\nWe are based in Ghana and work with clients at home and abroad.",
            ]],
            ['featured_projects', [
                'heading' => 'Selected work',
                'limit' => 4,
                'cta_label' => 'All work',
                'cta_url' => '/work',
            ]],
            ['featured_products', [
                'eyebrow' => 'Products',
                'heading' => 'Software we build for ourselves',
                'intro' => 'Besides client work, we build our own products, starting with DXCOVR Clinic ERP.',
                'limit' => 2,
                'cta_label' => 'All products',
                'cta_url' => '/products',
            ]],
            ['latest_articles', [
                'eyebrow' => 'Insights',
                'heading' => 'Notes on building what is next',
                'intro' => null,
                'limit' => 3,
                'cta_label' => 'All insights',
                'cta_url' => '/insights',
            ]],
            ['founder_intro', [
                'eyebrow' => 'About DXCOVR',
                'heading' => 'A multidisciplinary practice with an *engineer’s discipline*',
                'body' => "DXCOVR was founded by Anthony Jnr Ahiable to bring software, design, systems thinking and IT under one roof.\n\nWe care about the details that make technology reliable: clear structure, careful design and honest communication.",
                'show_founder' => true,
                'cta_label' => 'More about DXCOVR',
                'cta_url' => '/about',
            ]],
            ['enquiry', [
                'eyebrow' => 'Start a project',
                'heading' => 'Have a problem worth *solving?*',
                'body' => 'Tell us what you are working on. Whether it is a system to rebuild, a new product or a new technology, we will reply with honest advice on the next step.',
                'show_form' => true,
            ]],
            ['newsletter', ['heading' => null, 'body' => null]],
            // Hidden until real clients (with permission to be named) are added in the admin.
            ['clients', ['heading' => 'Clients', 'intro' => null, 'items' => []]],
        ]);

        $this->systemPage('about', 'About DXCOVR', 'DXCOVR is a technology company based in Ghana. We build business systems, AI tools, connected devices and immersive experiences that solve real problems.', [
            ['about_overview', [
                'lead' => 'We bring software, hardware and design together in one team, so your project has fewer hand-offs and fewer gaps.',
                'body' => 'We work with businesses, startups and organisations in Ghana and beyond. We understand the problem, shape the solution, build it well and support it after launch.',
                'stats' => [
                    ['value' => '7', 'label' => 'Service disciplines', 'note' => 'From software and AI to 3D and design'],
                    ['value' => '4', 'label' => 'Step process', 'note' => 'Understand, shape, build, support'],
                    ['value' => '1', 'label' => 'Product of our own', 'note' => 'DXCOVR Clinic ERP'],
                ],
            ]],
            ['services_showcase', [
                'heading' => 'What we do',
                'intro' => 'Seven connected services, built to work together. Here is how each one helps.',
                'cta_label' => 'Explore this service',
            ]],
            // Hidden until real, verified milestones are added in the admin.
            ['milestones', ['eyebrow' => null, 'heading' => 'History', 'intro' => null, 'items' => []]],
            ['mission', [
                'media_id' => null,
                'heading' => 'Our mission',
                'columns' => [
                    ['body' => 'To build smart, reliable technology that helps organisations solve real problems.', 'link_label' => 'How we work', 'link_url' => '/services'],
                    ['body' => 'We want organisations of every size, in Ghana and beyond, to use new technology in practical and responsible ways.', 'link_label' => null, 'link_url' => null],
                    ['body' => 'We choose the simplest solution that works, explain it in plain language and stay with you after launch.', 'link_label' => null, 'link_url' => null],
                ],
                'photo_one_media_id' => null,
                'photo_two_media_id' => null,
            ]],
            ['link_cards', [
                'heading' => 'Get to know DXCOVR',
                'items' => [
                    ['label' => 'Leadership', 'button_label' => 'Meet the founder', 'url' => '/about/founder', 'media_id' => null, 'glyph' => 'design'],
                    ['label' => 'Careers', 'button_label' => 'Life at DXCOVR', 'url' => '/careers', 'media_id' => null, 'glyph' => 'software'],
                    ['label' => 'Our work', 'button_label' => 'See case studies', 'url' => '/work', 'media_id' => null, 'glyph' => 'modelling'],
                    ['label' => 'Insights', 'button_label' => 'Read our stories', 'url' => '/insights', 'media_id' => null, 'glyph' => 'ai'],
                    ['label' => 'Contact', 'button_label' => 'Start a project', 'url' => '/contact', 'media_id' => null, 'glyph' => 'webxr'],
                ],
            ]],
            ['latest_articles', [
                'eyebrow' => null,
                'heading' => 'Latest updates',
                'intro' => 'Company news, ideas and updates from our team.',
                'layout' => 'list',
                'limit' => 4,
                'cta_label' => 'All insights',
                'cta_url' => '/insights',
            ]],
        ]);

        $this->systemPage('services', 'Services', 'Seven connected services, from business systems and AI to hardware, immersive experiences, 3D and design.', [
            ['steps', [
                'eyebrow' => 'How we work',
                'heading' => 'One process, whatever the discipline',
                'intro' => 'Every project starts the same way: understand the problem, then build the simplest thing that solves it well.',
                'items' => $steps,
            ]],
            ['faq', ['eyebrow' => 'FAQ', 'heading' => 'Working with DXCOVR', 'group' => 'projects']],
            ['cta_banner', [
                'heading' => 'Not sure which service you need?',
                'body' => 'Describe your problem and we will tell you which skills it needs.',
                'cta_label' => 'Talk to us',
                'cta_url' => '/contact',
            ]],
        ]);

        $this->systemPage('work', 'Work', 'Case studies, products and experiments, clearly labelled so you know what we built for clients.');
        $this->systemPage('products', 'Products', 'Software products designed, built and maintained by DXCOVR.');
        $this->systemPage('insights', 'Insights', 'Ideas on software, AI, connected devices, immersive technology and design, from the people building it.');

        $this->systemPage('careers', 'Careers', 'We are building a team of engineers, designers and technologists. See open roles and how we work.', [
            ['feature_grid', [
                'eyebrow' => 'Working at DXCOVR',
                'heading' => 'How we work together',
                'intro' => 'The principles we hold ourselves to, whatever the role.',
                'items' => [
                    ['title' => 'Curiosity with purpose', 'body' => 'We explore new technologies because they might solve a real problem, not because they are new.', 'icon' => 'ai'],
                    ['title' => 'Ownership', 'body' => 'Everyone owns their work from idea to delivery, and asks for help early.', 'icon' => 'software'],
                    ['title' => 'Craft', 'body' => 'We take pride in work that is well made, well tested and well documented.', 'icon' => 'design'],
                    ['title' => 'Learning', 'body' => 'We share what we learn and make time to get better at what we do.', 'icon' => 'modelling'],
                    ['title' => 'Clear communication', 'body' => 'We write things down, give honest feedback and respect each other’s time.', 'icon' => 'webxr'],
                    ['title' => 'Respect', 'body' => 'We value different backgrounds, perspectives and ways of working.', 'icon' => 'support'],
                ],
            ]],
            ['faq', ['eyebrow' => 'FAQ', 'heading' => 'Applying to DXCOVR', 'group' => 'careers']],
        ]);

        $this->systemPage('contact', 'Let’s talk about what you are building', 'Tell us about the problem you want to solve. We read every enquiry and reply personally.');
    }

    protected function legalPages(): void
    {
        $review = '<p><strong>Template notice:</strong> this page is a starting template. Replace the bracketed placeholders and have it reviewed for your jurisdiction (including Ghana’s Data Protection Act, 2012 (Act 843) where applicable) before publishing.</p>';

        $pages = [
            'privacy-policy' => ['Privacy policy', 'How DXCOVR collects, uses and protects personal information.', $review.'<h2>Who we are</h2><p>This website is operated by [legal company name] (“DXCOVR”, “we”, “us”), [registered address]. You can contact us about privacy at [privacy contact email].</p><h2>Information we collect</h2><ul><li><strong>Enquiries:</strong> when you contact us we collect your name, email address and message, and optionally your organisation, phone number, service of interest, project type, budget range and timeframe.</li><li><strong>Job applications:</strong> your name, email address, optional phone number, cover letter, CV and optional portfolio link.</li><li><strong>Newsletter:</strong> your email address and the date you confirmed your subscription, if the newsletter is enabled.</li><li><strong>Technical data:</strong> your IP address and browser user agent when you submit a form, used to protect against spam and abuse.</li></ul><h2>How we use it</h2><p>We use enquiry information only to respond to you and to manage any resulting work. We use application information only to assess your suitability for the role you applied for and closely related roles. We send newsletters only to people who have confirmed their subscription.</p><h2>How we protect it</h2><p>Information is stored in our website database and access is restricted to authorised staff by role. CVs are stored in private storage that is not publicly accessible. Administrative access is logged.</p><h2>How long we keep it</h2><p>Enquiries are kept for [period]. Job applications are deleted automatically [number] days after a final decision, or after submission if no decision is recorded. You can unsubscribe from the newsletter at any time using the link in every email.</p><h2>Your rights</h2><p>You may ask to access, correct or delete your personal information, or object to how it is used, by contacting [privacy contact email].</p><h2>Changes</h2><p>We will update this page if our practices change.</p>'],
            'cookie-policy' => ['Cookie policy', 'The cookies this website uses and why.', $review.'<h2>Cookies we use</h2><p>This website uses only cookies that are strictly necessary for it to work:</p><ul><li><strong>Session cookie:</strong> keeps forms working securely and keeps administrators signed in.</li><li><strong>XSRF-TOKEN:</strong> protects forms against cross-site request forgery.</li></ul><p>We do not use advertising or analytics cookies. If that changes, we will update this page and ask for your consent where required.</p><h2>Local storage</h2><p>If you dismiss a site announcement, your browser remembers that choice locally. It is not sent to us.</p>'],
            'terms-of-use' => ['Terms of use', 'The terms that apply when you use this website.', $review.'<h2>Using this website</h2><p>By using this website you agree to these terms. If you do not agree, please do not use the site.</p><h2>Content</h2><p>The content on this website is provided for general information. While we work to keep it accurate, it does not constitute professional advice and may change without notice.</p><h2>Intellectual property</h2><p>Unless stated otherwise, the content, design and graphics on this website belong to [legal company name] and may not be reproduced without permission.</p><h2>Links to other websites</h2><p>We are not responsible for the content of external websites linked from this site.</p><h2>Liability</h2><p>[Limitation of liability wording to be confirmed by your legal adviser.]</p><h2>Governing law</h2><p>These terms are governed by the laws of [jurisdiction].</p>'],
            'accessibility' => ['Accessibility statement', 'Our commitment to making this website usable by as many people as possible.', $review.'<h2>Our commitment</h2><p>We want everyone to be able to use this website. We aim to meet the Web Content Accessibility Guidelines (WCAG) 2.2 at level AA.</p><h2>What we have done</h2><ul><li>Text and interface colours are chosen to meet WCAG contrast requirements.</li><li>The site can be navigated with a keyboard, with visible focus indicators and a “skip to main content” link.</li><li>Images have text alternatives and forms have labels and clear error messages.</li><li>Animations respect the “reduce motion” setting on your device.</li></ul><h2>Known limitations</h2><p>[List any known issues, for example third-party video embeds or older documents.]</p><h2>Feedback</h2><p>If you find something difficult to use, please tell us at [accessibility contact email] and we will do our best to help.</p>'],
        ];

        foreach ($pages as $slug => [$title, $excerpt, $body]) {
            if (Page::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $page = new Page;
            $page->fill(['title' => $title, 'slug' => $slug, 'template' => 'legal', 'excerpt' => $excerpt, 'body' => $body]);
            $page->status = PublicationStatus::Draft;
            $page->save();
        }
    }

    /**
     * @param  array<string, Service>  $services
     */
    protected function navigation(array $services): void
    {
        $menus = [
            'primary' => [
                ['Services', '/services', null, collect($services)->map(fn (Service $service, string $slug) => [
                    $this->navLabels[$slug] ?? $service->title,
                    '/services/'.$slug,
                    Str::limit($service->short_description, 70),
                ])->values()->all()],
                ['Work', '/work'],
                ['Products', '/products'],
                ['Insights', '/insights'],
                ['About', '/about'],
                ['Careers', '/careers'],
            ],
            'footer_explore' => [['Services', '/services'], ['Work', '/work'], ['Products', '/products'], ['Insights', '/insights'], ['Search', '/search']],
            'footer_company' => [['About DXCOVR', '/about'], ['Founder', '/about/founder'], ['Careers', '/careers'], ['Contact', '/contact']],
            'footer_legal' => [['Privacy policy', '/privacy-policy'], ['Cookie policy', '/cookie-policy'], ['Terms of use', '/terms-of-use'], ['Accessibility', '/accessibility']],
        ];

        foreach ($menus as $key => $items) {
            $menu = NavigationMenu::query()->firstOrCreate(['key' => $key], ['name' => NavigationMenu::LOCATIONS[$key]]);

            if ($menu->items()->exists()) {
                continue;
            }

            foreach ($items as $position => $item) {
                $parent = $menu->items()->create([
                    'label' => $item[0],
                    'url' => $item[1],
                    'description' => $item[2] ?? null,
                    'sort_order' => $position,
                    'is_enabled' => true,
                ]);

                foreach ($item[3] ?? [] as $childPosition => [$label, $url, $description]) {
                    $menu->items()->create([
                        'parent_id' => $parent->id,
                        'label' => $label,
                        'url' => $url,
                        'description' => $description,
                        'sort_order' => $childPosition,
                        'is_enabled' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Create a system page (once) with its initial sections.
     *
     * @param  list<array{0: string, 1: array<string, mixed>}>  $sections
     */
    protected function systemPage(string $key, string $title, ?string $excerpt, array $sections = []): void
    {
        if (Page::query()->where('system_key', $key)->exists()) {
            return;
        }

        $page = new Page;
        $page->fill(['title' => $title, 'slug' => $key === 'home' ? 'home' : $key, 'template' => 'standard', 'excerpt' => $excerpt]);
        $page->system_key = $key;
        $page->template = 'system';
        $this->markPublished($page);

        foreach ($sections as $position => [$type, $data]) {
            $page->sections()->create([
                'type' => $type,
                'sort_order' => $position,
                'is_enabled' => true,
                'data' => $data,
            ]);
        }
    }

    protected function markPublished(Model $model): void
    {
        $model->status = PublicationStatus::Published;
        $model->published_at = now();
        $model->save();
    }
}
