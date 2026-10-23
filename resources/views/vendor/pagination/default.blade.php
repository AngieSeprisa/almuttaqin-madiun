@if ($paginator->hasPages())
    <nav class="custom-pagination-nav" role="navigation" aria-label="Navigasi Halaman">
        <div class="pagination-info">
            <span>Menampilkan</span>
            <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
            <span>–</span>
            <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
            <span>dari</span>
            <strong>{{ $paginator->total() }}</strong>
            <span>santri</span>
        </div>

        <ul class="pagination-list">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="pagination-item disabled" aria-disabled="true" aria-label="Sebelumnya">
                    <span class="pagination-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </span>
                </li>
            @else
                <li class="pagination-item">
                    <a href="{{ $paginator->previousPageUrl() }}" class="pagination-link" rel="prev" aria-label="Sebelumnya">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </a>
                </li>
            @endif

            {{-- Nomor Halaman --}}
            @foreach ($elements as $element)
                {{-- Separator Titik Tiga --}}
                @if (is_string($element))
                    <li class="pagination-item disabled"><span class="pagination-link dots">{{ $element }}</span></li>
                @endif

                {{-- Array Halaman --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pagination-item active" aria-current="page">
                                <span class="pagination-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="pagination-item">
                                <a href="{{ $url }}" class="pagination-link">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol Selanjutnya --}}
            @if ($paginator->hasMorePages())
                <li class="pagination-item">
                    <a href="{{ $paginator->nextPageUrl() }}" class="pagination-link" rel="next" aria-label="Selanjutnya">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </li>
            @else
                <li class="pagination-item disabled" aria-disabled="true" aria-label="Selanjutnya">
                    <span class="pagination-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
