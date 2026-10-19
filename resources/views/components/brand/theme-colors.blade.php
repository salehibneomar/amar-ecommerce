<style>
    :root {
        @foreach (themePalettes() as $palette => $shades)
            @foreach ($shades as $shade => $color)
                --theme-{{ $palette }}-{{ $shade }}: {{ $color }};
            @endforeach
        @endforeach
        --theme-brand: {{ themeBrandColor() }};
        --theme-brand-dark: {{ themeBrandDarkColor() }};
        --theme-brand-foreground: {{ themeBrandForeground() }};
    }
</style>
