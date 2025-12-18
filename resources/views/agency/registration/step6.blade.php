@extends('agency.registration.layout')

@section('form-content')
    <form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6">
        @csrf

        <div x-data="{ 
                references: {{ json_encode(old('references', $data['references'] ?? [['company_name' => '', 'contact_name' => '', 'contact_email' => '', 'contact_phone' => '', 'relationship' => '']])) }}
            }">
            <div class="space-y-8">
                <template x-for="(ref, index) in references" :key="index">
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 relative">
                        <div class="absolute top-4 right-4" x-show="references.length > 2">
                            <button type="button" @click="references = references.filter((_, i) => i !== index)"
                                class="text-gray-400 hover:text-red-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4" x-text="'Reference #' + (index + 1)"></h3>

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Company Name -->
                            <div class="sm:col-span-3">
                                <label :for="'company_name_' + index"
                                    class="block text-sm font-medium text-gray-700">Company Name</label>
                                <div class="mt-1">
                                    <input type="text" :name="'references[' + index + '][company_name]'"
                                        :id="'company_name_' + index" x-model="ref.company_name" required
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Use 'relationship' field as Relationship -->
                            <div class="sm:col-span-3">
                                <label :for="'relationship_' + index"
                                    class="block text-sm font-medium text-gray-700">Relationship</label>
                                <div class="mt-1">
                                    <select :name="'references[' + index + '][relationship]'" :id="'relationship_' + index"
                                        x-model="ref.relationship" required
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="">Select relationship</option>
                                        <option value="client">Client</option>
                                        <option value="partner">Partner</option>
                                        <option value="vendor">Vendor</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Contact Name -->
                            <div class="sm:col-span-3">
                                <label :for="'contact_name_' + index"
                                    class="block text-sm font-medium text-gray-700">Contact Person</label>
                                <div class="mt-1">
                                    <input type="text" :name="'references[' + index + '][contact_name]'"
                                        :id="'contact_name_' + index" x-model="ref.contact_name" required
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Contact Phone -->
                            <div class="sm:col-span-3">
                                <label :for="'contact_phone_' + index"
                                    class="block text-sm font-medium text-gray-700">Contact Phone</label>
                                <div class="mt-1">
                                    <input type="tel" :name="'references[' + index + '][contact_phone]'"
                                        :id="'contact_phone_' + index" x-model="ref.contact_phone" required
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Contact Email -->
                            <div class="sm:col-span-4">
                                <label :for="'contact_email_' + index"
                                    class="block text-sm font-medium text-gray-700">Contact Email</label>
                                <div class="mt-1">
                                    <input type="email" :name="'references[' + index + '][contact_email]'"
                                        :id="'contact_email_' + index" x-model="ref.contact_email" required
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Years Known -->
                            <div class="sm:col-span-2">
                                <label :for="'years_known_' + index" class="block text-sm font-medium text-gray-700">Years
                                    Known</label>
                                <div class="mt-1">
                                    <input type="number" :name="'references[' + index + '][years_known]'"
                                        :id="'years_known_' + index" x-model="ref.years_known" min="0"
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4" x-show="references.length < 3">
                <button type="button"
                    @click="references.push({company_name: '', contact_name: '', contact_email: '', contact_phone: '', relationship: '', years_known: ''})"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Another Reference
                </button>
            </div>

            @if ($errors->any())
                <div class="mt-4 rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="pt-5 flex justify-between">
            <a href="{{ route('agency.register.previous', $step) }}"
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Back
            </a>
            <button type="submit"
                class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save & Continue
            </button>
        </div>
    </form>
@endsection