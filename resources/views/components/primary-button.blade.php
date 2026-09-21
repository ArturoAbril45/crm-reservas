<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex w-full items-center justify-center rounded-lg bg-[#9c0720] px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-[#7c0519] focus:outline-none focus:ring-2 focus:ring-[#9c0720] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
