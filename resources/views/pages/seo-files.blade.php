<x-filament-panels::page>
    @foreach ($this->getSections() as $section)
        <x-filament::section :heading="$section['title']" :description="$section['help']">
            <div class="fi-seo-files-row">
                <p class="fi-seo-files-status">{{ $section['status'] }}</p>

                <div class="fi-seo-files-actions">
                    @foreach ($section['actions'] as $action)
                        {{ $this->{$action} }}
                    @endforeach
                </div>
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
