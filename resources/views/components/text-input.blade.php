@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700 disabled:bg-gray-50 disabled:text-gray-500']) }}>
