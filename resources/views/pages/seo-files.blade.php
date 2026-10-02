<x-filament-panels::page>
    @foreach ($this->getSections() as $section)
        <x-filament::section :heading="$section['title']" :description="$section['help']">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $section['status'] }}</p>

                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($section['actions'] as $action)
                        {{ $this->{$action} }}
                    @endforeach
                </div>
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
