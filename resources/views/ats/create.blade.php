@extends('layouts.employer')

@section('title', 'Add ATS Integration')

@section('content')
<div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('ats.index') }}" class="text-gray-400 hover:text-gray-500">
                        <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('ats.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">ATS Integrations</a>
                </li>
            </ol>
        </nav>
        <h2 class="mt-4 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
            Add ATS Integration
        </h2>
    </div>

    <!-- Provider Selection (if no provider selected) -->
    @if(!$provider)
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Select Your ATS Provider</h3>
                <p class="mt-1 text-sm text-gray-500">Choose the Applicant Tracking System you want to connect</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 p-6">
                @foreach($providers as $p)
                    <a href="{{ route('ats.create', ['provider' => $p->id]) }}" class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-primary-400 hover:ring-2 hover:ring-primary-200 transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 h-12 w-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                @if($p->logo_url)
                                    <img src="{{ $p->logo_url }}" alt="{{ $p->name }}" class="h-8 w-8">
                                @else
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $p->name }}</p>
                                <p class="text-sm text-gray-500">{{ ucfirst($p->auth_type) }} authentication</p>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <!-- Connection Form -->
        <form action="{{ route('ats.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="ats_provider_id" value="{{ $provider->id }}">

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            @if($provider->logo_url)
                                <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" class="h-6 w-6">
                            @else
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Connect {{ $provider->name }}</h3>
                            <p class="text-sm text-gray-500">Enter your {{ $provider->name }} credentials to connect</p>
                        </div>
                    </div>
                </div>
                
                <div class="px-4 py-5 sm:p-6 space-y-6">
                    <!-- Connection Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Connection Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $provider->name . ' Integration') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                        <p class="mt-1 text-sm text-gray-500">A friendly name to identify this connection</p>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Provider-specific credentials -->
                    @if($provider->auth_type === 'api_key')
                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-700">API Key</label>
                            <input type="password" name="credentials[api_key]" id="api_key" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                            <p class="mt-1 text-sm text-gray-500">Find this in your {{ $provider->name }} settings under API or Integrations</p>
                            @error('credentials.api_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(in_array($provider->slug, ['bamboohr']))
                            <div>
                                <label for="subdomain" class="block text-sm font-medium text-gray-700">Subdomain</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" name="credentials[subdomain]" id="subdomain" class="flex-1 min-w-0 block w-full border-gray-300 rounded-l-md focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="yourcompany" required>
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">.bamboohr.com</span>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if($provider->auth_type === 'oauth2')
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        {{ $provider->name }} uses OAuth authentication. You'll be redirected to {{ $provider->name }} to authorize the connection.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client ID</label>
                            <input type="text" name="credentials[client_id]" id="client_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                        </div>

                        <div>
                            <label for="client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                            <input type="password" name="credentials[client_secret]" id="client_secret" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                        </div>

                        @if(in_array($provider->slug, ['workday']))
                            <div>
                                <label for="tenant" class="block text-sm font-medium text-gray-700">Tenant ID</label>
                                <input type="text" name="credentials[tenant]" id="tenant" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label for="data_center" class="block text-sm font-medium text-gray-700">Data Center</label>
                                <select name="credentials[data_center]" id="data_center" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    <option value="wd2">wd2 (US)</option>
                                    <option value="wd5">wd5 (US)</option>
                                    <option value="wd3">wd3 (EU)</option>
                                    <option value="wd4">wd4 (Canada)</option>
                                </select>
                            </div>
                        @endif

                        @if(in_array($provider->slug, ['successfactors']))
                            <div>
                                <label for="api_server" class="block text-sm font-medium text-gray-700">API Server</label>
                                <input type="text" name="credentials[api_server]" id="api_server" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="api.successfactors.com" required>
                            </div>
                        @endif

                        @if(in_array($provider->slug, ['icims']))
                            <div>
                                <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer ID</label>
                                <input type="text" name="credentials[customer_id]" id="customer_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                            </div>
                        @endif

                        @if(in_array($provider->slug, ['taleo']))
                            <div>
                                <label for="company_code" class="block text-sm font-medium text-gray-700">Company Code</label>
                                <input type="text" name="credentials[company_code]" id="company_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 rounded-b-lg">
                    <a href="{{ route('ats.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 mr-3">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        @if($provider->auth_type === 'oauth2')
                            Connect with {{ $provider->name }}
                        @else
                            Save Connection
                        @endif
                    </button>
                </div>
            </div>
        </form>

        <!-- Setup Instructions -->
        <div class="mt-6 bg-gray-50 rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-900">How to get your credentials</h3>
            <ol class="mt-3 text-sm text-gray-600 list-decimal list-inside space-y-2">
                @if($provider->slug === 'lever')
                    <li>Log in to your Lever account</li>
                    <li>Go to Settings → Integrations and API</li>
                    <li>Generate a new API key with appropriate permissions</li>
                    <li>Copy the API key and paste it above</li>
                @elseif($provider->slug === 'greenhouse')
                    <li>Log in to Greenhouse as an admin</li>
                    <li>Navigate to Configure → Dev Center → API Credentials</li>
                    <li>Create a new Harvest API key</li>
                    <li>Select the appropriate permissions for candidates and jobs</li>
                @elseif($provider->slug === 'bamboohr')
                    <li>Log in to your BambooHR account</li>
                    <li>Go to Account → API Keys</li>
                    <li>Add a new API key</li>
                    <li>Copy the key and enter your subdomain</li>
                @else
                    <li>Log in to your {{ $provider->name }} admin account</li>
                    <li>Navigate to Settings or Integrations</li>
                    <li>Create API credentials or OAuth application</li>
                    <li>Copy the credentials and enter them above</li>
                @endif
            </ol>
        </div>
    @endif
</div>
@endsection
