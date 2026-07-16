@if ($paginator->hasPages())
    <nav>
        <ul class="pagination" style="display: flex; list-style: none; padding: 0; margin: 0; gap: 4px;">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li style="opacity: 0.5;">
                    <span class="page-link">«</span>
                </li>
            @else
                <li>
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}">«</a>
                </li>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="page-link"
                                    style="background-color: var(--accent) !important; color: white !important; border-color: var(--accent) !important;">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}">»</a>
                </li>
            @else
                <li style="opacity: 0.5;">
                    <span class="page-link">»</span>
                </li>
            @endif

        </ul>
    </nav>
@endif