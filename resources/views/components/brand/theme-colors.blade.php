<style>
    :root {
        @foreach (themePalettes() as $palette => $shades)
            @foreach ($shades as $shade => $color)
                --theme-{{ $palette }}-{{ $shade }}: {{ $color }};
            @endforeach
        @endforeach
        @foreach ($theme['palette'] as $shade => $color)
            --theme-brand-{{ $shade }}: {{ $color }};
        @endforeach
        --theme-brand: {{ $theme['brand'] }};
        --theme-brand-hover: {{ $theme['brand_hover'] }};
        --theme-brand-dark: {{ $theme['brand_dark'] }};
        --theme-brand-foreground: {{ $theme['foreground'] }};
    }
</style>
