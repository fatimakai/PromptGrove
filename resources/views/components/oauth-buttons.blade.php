<div class="space-y-3">
    <a href="{{ route('oauth.redirect', 'google') }}" class="flex w-full items-center justify-center gap-3 rounded-full border border-gray-900 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-amber-50 focus:outline-none">
        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z"/>
            <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.64-2.43l-3.24-2.54c-.9.6-2.05.97-3.4.97-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"/>
            <path fill="#FBBC05" d="M6.39 13.87A6.02 6.02 0 0 1 6.07 12c0-.65.11-1.28.32-1.87V7.51H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.49l3.35-2.62Z"/>
            <path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.83 1.5l2.88-2.88A9.66 9.66 0 0 0 12 2a10 10 0 0 0-8.96 5.51l3.35 2.62C7.18 7.76 9.39 6 12 6Z"/>
        </svg>
        Continue with Google
    </a>

    <a href="{{ route('oauth.redirect', 'github') }}" class="flex w-full items-center justify-center gap-3 rounded-full border border-gray-900 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-amber-50 focus:outline-none">
        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 .7a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2.23c-3.22.7-3.9-1.37-3.9-1.37-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.71.08-.71 1.16.08 1.78 1.2 1.78 1.2 1.04 1.77 2.72 1.26 3.38.96.1-.75.4-1.26.74-1.55-2.57-.29-5.27-1.29-5.27-5.69 0-1.26.45-2.28 1.19-3.09-.12-.29-.52-1.47.11-3.05 0 0 .97-.31 3.16 1.18a10.98 10.98 0 0 1 5.75 0c2.19-1.49 3.16-1.18 3.16-1.18.63 1.58.23 2.76.11 3.05.74.81 1.19 1.83 1.19 3.09 0 4.42-2.71 5.39-5.29 5.68.42.36.79 1.07.79 2.16v3.25c0 .31.21.67.79.56A11.5 11.5 0 0 0 12 .7Z"/>
        </svg>
        Continue with GitHub
    </a>
</div>

<div class="my-6 flex items-center gap-3" aria-hidden="true">
    <div class="h-px flex-1 bg-gray-300"></div>
    <span class="grove-mono text-[10px] font-medium uppercase tracking-wider text-gray-500">or use email</span>
    <div class="h-px flex-1 bg-gray-300"></div>
</div>
