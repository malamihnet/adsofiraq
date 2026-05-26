<aside class="w-64 border-r border-archive-border bg-archive-light p-6">
    <x-logo size="sm" class="mb-8" />
    <p class="section-label mb-4">Admin</p>
    <nav class="space-y-1 text-sm">
        <a href="{{ route('admin.dashboard') }}" class="block py-2 hover:underline">Dashboard</a>
        <a href="{{ route('admin.campaigns.index') }}" class="block py-2 hover:underline">Campaigns</a>
        <a href="{{ route('admin.campaigns.create') }}" class="block py-2 pl-4 text-archive-gray hover:underline">Add Campaign</a>
        <a href="{{ route('admin.import-campaign.create') }}" class="block py-2 pl-4 text-archive-gray hover:underline">Import Campaign</a>
        <a href="{{ route('admin.check-new-campaigns.index') }}" class="block py-2 pl-4 text-archive-gray hover:underline">Check New Campaigns</a>
        <a href="{{ route('admin.brands.index') }}" class="block py-2 hover:underline">Brands</a>
        <a href="{{ route('admin.agencies.index') }}" class="block py-2 hover:underline">Agencies</a>
        <a href="{{ route('admin.people.index') }}" class="block py-2 hover:underline">People</a>
        <a href="{{ route('admin.industries.index') }}" class="block py-2 hover:underline">Industries</a>
        <a href="{{ route('admin.medium-types.index') }}" class="block py-2 hover:underline">Medium Types</a>
        <a href="{{ route('admin.countries.index') }}" class="block py-2 hover:underline">Countries</a>
        <a href="{{ route('admin.users.index') }}" class="block py-2 hover:underline">Users</a>
        <hr class="my-4 border-archive-border">
        <a href="{{ route('home') }}" class="block py-2 hover:underline">Back to site</a>
    </nav>
</aside>
