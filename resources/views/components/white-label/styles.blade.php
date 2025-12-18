{{-- White-Label CSS Styles Component --}}
{{-- Include this in the <head> section of white-label pages --}}

@if(isset($isWhiteLabel) && $isWhiteLabel && isset($whiteLabelCss))
<style id="white-label-styles">
{!! $whiteLabelCss !!}
</style>
@endif

{{-- Inline CSS variables for components that need them --}}
@if(isset($cssVariables) && $cssVariables)
<style id="white-label-variables">
:root {
    {{ $cssVariables }}
}
</style>
@endif
