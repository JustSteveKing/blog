@php
    /* @var array $nav */
    /* @var array|null|string $currentTag */
    /* @var array|null|string $currentCategory */
    /* @var \Illuminate\Support\Collection<int,\Prezet\Prezet\Data\DocumentData> $articles */
    /* @var \Illuminate\Support\Collection $postsByYear */
    /* @var \Illuminate\Support\Collection $allCategories */
    /* @var \Illuminate\Support\Collection $allTags */
@endphp

<x-prezet.template>
    @seo([
        'title' => 'Prezet: Markdown Blogging for Laravel',
        'description' =>
            'Transform your markdown files into SEO-friendly blogs, articles, and documentation!',
        'url' => route('prezet.index'),
    ])

    <div class="mx-auto max-w-4xl">
        <h1
            class="mb-2 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl dark:text-white"
        >
            All Posts
        </h1>

        <div class="mb-12 justify-between sm:flex md:mb-16">
            <p class="text-lg text-zinc-700 dark:text-zinc-300">
                A blog created with Laravel and Tailwind.css
            </p>
            <div class="mt-4 inline-flex flex-wrap justify-center gap-2 sm:mt-0" role="list" aria-label="Active filters">
                <p class="text-md text-zinc-700 dark:text-zinc-300">{{ $articles->count() }} Articles</p>
                @if ($currentTag)
                    <span
                        class="inline-flex items-center gap-x-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        role="listitem"
                    >
                        <x-prezet.icon-tag class="size-3" />
                        {{ strtoupper($currentTag) }}
                        <a
                            href="{{ route('prezet.index', array_filter(request()->except('tag'))) }}"
                            class="group relative -mr-1 flex h-4 w-4 items-center justify-center rounded-full hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 dark:hover:bg-zinc-700 dark:focus:ring-zinc-100"
                            aria-label="Remove {{ $currentTag }} tag filter"
                        >
                            <span class="sr-only">Remove</span>
                            <svg
                                viewBox="0 0 14 14"
                                class="h-3 w-3 stroke-zinc-600 group-hover:stroke-zinc-900 dark:stroke-zinc-300 dark:group-hover:stroke-zinc-100"
                                aria-hidden="true"
                            >
                                <path d="M4 4l6 6m0-6l-6 6" />
                            </svg>
                        </a>
                    </span>
                @endif

                @if ($currentCategory)
                    <span
                        class="inline-flex items-center gap-x-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        role="listitem"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="size-3"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"
                            />
                        </svg>
                        {{ $currentCategory }}
                        <a
                            href="{{ route('prezet.index', array_filter(request()->except('category'))) }}"
                            class="group relative -mr-1 flex h-4 w-4 items-center justify-center rounded-full hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 dark:hover:bg-zinc-700 dark:focus:ring-zinc-100"
                            aria-label="Remove {{ $currentCategory }} category filter"
                        >
                            <span class="sr-only">Remove</span>
                            <svg
                                viewBox="0 0 14 14"
                                class="h-3 w-3 stroke-zinc-600 group-hover:stroke-zinc-900 dark:stroke-zinc-300 dark:group-hover:stroke-zinc-100"
                                aria-hidden="true"
                            >
                                <path d="M4 4l6 6m0-6l-6 6" />
                            </svg>
                        </a>
                    </span>
                @endif

                @if ($currentAuthor)
                    <span
                        class="inline-flex items-center gap-x-1 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        role="listitem"
                    >
                        <img
                            src="{{ $currentAuthor['image'] }}"
                            alt=""
                            class="h-4 w-4 rounded-full"
                            aria-hidden="true"
                        />
                        {{ $currentAuthor['name'] }}
                        <a
                            href="{{ route('prezet.index', array_filter(request()->except('author'))) }}"
                            class="group relative -mr-1 flex h-4 w-4 items-center justify-center rounded-full hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 dark:hover:bg-zinc-700 dark:focus:ring-zinc-100"
                            aria-label="Remove {{ $currentAuthor['name'] }} author filter"
                        >
                            <span class="sr-only">Remove</span>
                            <svg
                                viewBox="0 0 14 14"
                                class="h-3 w-3 stroke-zinc-600 group-hover:stroke-zinc-900 dark:stroke-zinc-300 dark:group-hover:stroke-zinc-100"
                                aria-hidden="true"
                            >
                                <path d="M4 4l6 6m0-6l-6 6" />
                            </svg>
                        </a>
                    </span>
                @endif
            </div>
        </div>

        @foreach ($postsByYear as $year => $posts)
            <section class="mb-16" aria-labelledby="year-{{ $year }}">
                <h2
                    id="year-{{ $year }}"
                    class="mb-8 text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500"
                >
                    {{ $year }}
                </h2>

                <div class="space-y-8">
                    @foreach ($posts as $post)
                        <x-prezet.article
                            :article="$post"
                            :author="config('prezet.authors.' . $post->frontmatter->author)"
                        />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-prezet.template>
