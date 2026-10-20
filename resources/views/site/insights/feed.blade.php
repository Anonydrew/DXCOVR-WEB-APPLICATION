{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $site->get('site.name') }} Insights</title>
        <link>{{ route('insights.index') }}</link>
        <description>{{ $site->get('site.description') }}</description>
        <language>en-gb</language>
        <atom:link href="{{ route('insights.feed') }}" rel="self" type="application/rss+xml" />
        @if ($articles->isNotEmpty())
            <lastBuildDate>{{ $articles->first()->published_at->toRssString() }}</lastBuildDate>
        @endif
        @foreach ($articles as $article)
            <item>
                <title>{{ $article->title }}</title>
                <link>{{ route('insights.show', $article) }}</link>
                <guid isPermaLink="true">{{ route('insights.show', $article) }}</guid>
                <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
                <description>{{ $article->excerpt }}</description>
                @foreach ($article->categories as $category)
                    <category>{{ $category->name }}</category>
                @endforeach
            </item>
        @endforeach
    </channel>
</rss>
