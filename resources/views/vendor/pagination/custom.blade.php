{{-- resources/views/vendor/pagination/custom.blade.php --}}
@if ($paginator->hasPages())
    <nav class="mariam-pagination" aria-label="Pagination">
        <div class="mariam-pagination-info">
            Affichage de <strong>{{ $paginator->firstItem() }}</strong> à <strong>{{ $paginator->lastItem() }}</strong>
            sur <strong>{{ $paginator->total() }}</strong> résultat{{ $paginator->total() > 1 ? 's' : '' }}
        </div>

        <ul class="mariam-pagination-list">
            {{-- Précédent --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="mariam-page-nav disabled">
                        <i class="fas fa-chevron-left"></i>&nbsp;Précédent
                    </span>
                </li>
            @else
                <li>
                    <a class="mariam-page-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="fas fa-chevron-left"></i>&nbsp;Précédent
                    </a>
                </li>
            @endif

            {{-- Numéros de page --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="mariam-page-dots">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="mariam-page-link active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="mariam-page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Suivant --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a class="mariam-page-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        Suivant&nbsp;<i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            @else
                <li>
                    <span class="mariam-page-nav disabled">
                        Suivant&nbsp;<i class="fas fa-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif