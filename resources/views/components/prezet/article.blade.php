<article
    class="group border-b border-zinc-200 pb-8 transition-colors hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600"
>
    <div class="space-y-3">
        @if ($article->category)
            <a
                href="{{ route('prezet.show', ['slug' => strtolower($article->category)]) }}"
                class="inline-block text-xs font-medium uppercase tracking-wide text-zinc-600 transition-colors hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:text-zinc-400 dark:hover:text-zinc-200 dark:focus:ring-zinc-100 dark:focus:ring-offset-zinc-900"
                aria-label="View all posts in {{ $article->category }} category"
            >
                {{ $article->category }}
            </a>
        @endif

        <h2
            class="text-2xl font-semibold leading-tight tracking-tight transition-colors group-hover:text-zinc-700 dark:text-white dark:group-hover:text-zinc-200"
        >
            <a
                href="{{ route('prezet.show', $article->slug) }}"
                class="focus:outline-none focus:underline focus:decoration-2 focus:underline-offset-4"
            >
                {{ $article->frontmatter->title }}
            </a>
        </h2>

        <p class="text-base leading-relaxed text-zinc-700 dark:text-zinc-300">
            {{ $article->frontmatter->excerpt }}
        </p>

        <div class="flex items-center gap-4 pt-2 text-sm text-zinc-600 dark:text-zinc-400">
            <a
                href="{{ route('prezet.index', ['author' => strtolower($article->frontmatter->author)]) }}"
                class="flex items-center gap-2 transition-colors hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 focus:rounded dark:hover:text-zinc-200 dark:focus:ring-zinc-100 dark:focus:ring-offset-zinc-900"
                aria-label="View all posts by {{ $author['name'] ?? 'Guest' }}"
            >
                <img
                    src="{{ $author['image'] ?? '' }}"
                    alt="{{ $author['name'] ?? 'Author' }}"
                    class="h-6 w-6 rounded-full"
                />
                <span>{{ $author['name'] ?? 'Guest' }}</span>
            </a>
            <span class="text-zinc-400 dark:text-zinc-600" aria-hidden="true">•</span>
            <time datetime="{{ $article->createdAt->toIso8601String() }}">
                {{ $article->createdAt->format('M j, Y') }}
            </time>
        </div>

        @if (count($article->frontmatter->tags) > 0)
            <div class="flex flex-wrap gap-2 pt-1">
                @foreach ($article->frontmatter->tags as $tag)
                    <a
                        href="{{ route('prezet.index', ['tag' => strtolower($tag)]) }}"
                        class="text-xs text-zinc-600 transition-colors hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-1 focus:rounded dark:text-zinc-400 dark:hover:text-zinc-200 dark:focus:ring-zinc-100 dark:focus:ring-offset-zinc-900"
                        aria-label="View all posts tagged with {{ $tag }}"
                    >
                        #{{ $tag }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</article>
