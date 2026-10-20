@props(['data'])
{{-- JSON_HEX_TAG keeps "</script>" in any field from closing the tag early. --}}
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
