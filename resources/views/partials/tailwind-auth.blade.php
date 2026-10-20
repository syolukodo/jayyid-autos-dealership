{{-- Tailwind (CDN preview build) with preflight disabled so main.css base styles stay intact. --}}
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
  tailwind.config = {
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          navy: '#0d3b66',
          'navy-dark': '#0a2c4e',
          canvas: '#f8fafc',
          coral: '#f95738',
          'coral-dark': '#e04a2c',
          success: '#16a34a'
        },
        boxShadow: {
          card: '0 1px 3px rgba(15, 23, 42, 0.05), 0 8px 22px rgba(15, 23, 42, 0.04)'
        }
      }
    }
  };
</script>
{{-- Validation feedback hook: main.js toggles .is-invalid on the input. --}}
<style>
  input[data-validate].is-invalid {
    border-color: #f95738 !important;
    box-shadow: 0 0 0 3px rgba(249, 87, 56, 0.12);
  }
</style>
