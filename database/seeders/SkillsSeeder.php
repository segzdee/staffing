<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds common job skills for staffing marketplace across multiple industries.
     *
     * @return void
     */
    public function run()
    {
        $skills = [
            // General / Soft Skills
            ['name' => 'Customer Service', 'industry' => 'General', 'description' => 'Providing excellent customer service and support'],
            ['name' => 'Communication', 'industry' => 'General', 'description' => 'Effective verbal and written communication skills'],
            ['name' => 'Teamwork', 'industry' => 'General', 'description' => 'Working collaboratively with team members'],
            ['name' => 'Time Management', 'industry' => 'General', 'description' => 'Managing time effectively to meet deadlines'],
            ['name' => 'Problem Solving', 'industry' => 'General', 'description' => 'Identifying and solving problems efficiently'],
            ['name' => 'Leadership', 'industry' => 'General', 'description' => 'Leading and motivating teams'],
            ['name' => 'Multitasking', 'industry' => 'General', 'description' => 'Handling multiple tasks simultaneously'],
            ['name' => 'Attention to Detail', 'industry' => 'General', 'description' => 'Careful attention to accuracy and quality'],
            ['name' => 'Adaptability', 'industry' => 'General', 'description' => 'Quickly adapting to new situations and changes'],
            ['name' => 'Conflict Resolution', 'industry' => 'General', 'description' => 'Resolving conflicts and disputes professionally'],

            // Hospitality
            ['name' => 'Food Service', 'industry' => 'Hospitality', 'description' => 'Preparing and serving food in restaurants'],
            ['name' => 'Bartending', 'industry' => 'Hospitality', 'description' => 'Mixing and serving alcoholic and non-alcoholic drinks'],
            ['name' => 'Barista', 'industry' => 'Hospitality', 'description' => 'Preparing coffee, espresso, and specialty drinks'],
            ['name' => 'Server/Waiter', 'industry' => 'Hospitality', 'description' => 'Table service in restaurants and dining establishments'],
            ['name' => 'Host/Hostess', 'industry' => 'Hospitality', 'description' => 'Greeting and seating guests, managing reservations'],
            ['name' => 'Housekeeping', 'industry' => 'Hospitality', 'description' => 'Cleaning and maintaining hotel rooms and facilities'],
            ['name' => 'Front Desk', 'industry' => 'Hospitality', 'description' => 'Hotel reception and guest services'],
            ['name' => 'Banquet Service', 'industry' => 'Hospitality', 'description' => 'Serving at banquets and large events'],
            ['name' => 'Room Service', 'industry' => 'Hospitality', 'description' => 'Delivering food and amenities to hotel rooms'],
            ['name' => 'Kitchen Prep', 'industry' => 'Hospitality', 'description' => 'Food preparation and kitchen assistance'],
            ['name' => 'Line Cook', 'industry' => 'Hospitality', 'description' => 'Cooking on the line in restaurant kitchens'],
            ['name' => 'Dishwashing', 'industry' => 'Hospitality', 'description' => 'Operating dishwashers and maintaining kitchen cleanliness'],
            ['name' => 'Food Runner', 'industry' => 'Hospitality', 'description' => 'Delivering food from kitchen to tables'],
            ['name' => 'Busser', 'industry' => 'Hospitality', 'description' => 'Clearing and setting tables in restaurants'],

            // Retail
            ['name' => 'Cash Handling', 'industry' => 'Retail', 'description' => 'Processing payments and managing cash accurately'],
            ['name' => 'POS Systems', 'industry' => 'Retail', 'description' => 'Operating point-of-sale systems and registers'],
            ['name' => 'Inventory Management', 'industry' => 'Retail', 'description' => 'Tracking and managing store inventory'],
            ['name' => 'Sales', 'industry' => 'Retail', 'description' => 'Selling products and services to customers'],
            ['name' => 'Visual Merchandising', 'industry' => 'Retail', 'description' => 'Product display and store presentation'],
            ['name' => 'Stock Room Operations', 'industry' => 'Retail', 'description' => 'Managing backroom inventory and restocking'],
            ['name' => 'Loss Prevention', 'industry' => 'Retail', 'description' => 'Preventing theft and inventory shrinkage'],
            ['name' => 'Personal Shopping', 'industry' => 'Retail', 'description' => 'Assisting customers with personalized shopping'],
            ['name' => 'Product Knowledge', 'industry' => 'Retail', 'description' => 'Expert knowledge of products and services'],

            // Warehouse / Logistics
            ['name' => 'Forklift Operation', 'industry' => 'Warehouse', 'description' => 'Safely operating forklifts and warehouse equipment'],
            ['name' => 'Pallet Jack Operation', 'industry' => 'Warehouse', 'description' => 'Using manual and electric pallet jacks'],
            ['name' => 'Shipping and Receiving', 'industry' => 'Warehouse', 'description' => 'Processing incoming and outgoing shipments'],
            ['name' => 'Order Picking', 'industry' => 'Warehouse', 'description' => 'Selecting and preparing orders for shipment'],
            ['name' => 'Loading and Unloading', 'industry' => 'Warehouse', 'description' => 'Loading and unloading trucks and containers'],
            ['name' => 'Inventory Scanning', 'industry' => 'Warehouse', 'description' => 'Using RF scanners for inventory tracking'],
            ['name' => 'Packaging', 'industry' => 'Warehouse', 'description' => 'Packaging products for shipment'],
            ['name' => 'Quality Control', 'industry' => 'Warehouse', 'description' => 'Inspecting products for quality standards'],
            ['name' => 'Warehouse Management Systems', 'industry' => 'Warehouse', 'description' => 'Using WMS software for inventory management'],
            ['name' => 'Reach Truck Operation', 'industry' => 'Warehouse', 'description' => 'Operating reach trucks in narrow aisles'],
            ['name' => 'Cross-Docking', 'industry' => 'Warehouse', 'description' => 'Direct transfer of goods between vehicles'],

            // Healthcare
            ['name' => 'Patient Care', 'industry' => 'Healthcare', 'description' => 'Providing direct care to patients'],
            ['name' => 'CNA Skills', 'industry' => 'Healthcare', 'description' => 'Certified Nursing Assistant duties'],
            ['name' => 'Medical Terminology', 'industry' => 'Healthcare', 'description' => 'Understanding medical terms and abbreviations'],
            ['name' => 'Vital Signs Monitoring', 'industry' => 'Healthcare', 'description' => 'Taking and recording vital signs'],
            ['name' => 'Phlebotomy', 'industry' => 'Healthcare', 'description' => 'Drawing blood for tests'],
            ['name' => 'Medical Records', 'industry' => 'Healthcare', 'description' => 'Managing patient medical records'],
            ['name' => 'CPR/First Aid', 'industry' => 'Healthcare', 'description' => 'Emergency life-saving techniques'],
            ['name' => 'Medication Administration', 'industry' => 'Healthcare', 'description' => 'Administering medications to patients'],
            ['name' => 'Patient Transport', 'industry' => 'Healthcare', 'description' => 'Safely transporting patients'],
            ['name' => 'Elder Care', 'industry' => 'Healthcare', 'description' => 'Specialized care for elderly patients'],

            // Events
            ['name' => 'Event Setup', 'industry' => 'Events', 'description' => 'Setting up venues for events'],
            ['name' => 'Event Breakdown', 'industry' => 'Events', 'description' => 'Breaking down and cleaning up after events'],
            ['name' => 'Event Staffing', 'industry' => 'Events', 'description' => 'Working as general event staff'],
            ['name' => 'Catering Service', 'industry' => 'Events', 'description' => 'Food and beverage service at events'],
            ['name' => 'Registration Desk', 'industry' => 'Events', 'description' => 'Managing event check-in and registration'],
            ['name' => 'Audio/Visual Setup', 'industry' => 'Events', 'description' => 'Setting up AV equipment for events'],
            ['name' => 'Crowd Management', 'industry' => 'Events', 'description' => 'Managing crowds at events'],
            ['name' => 'Promotional Events', 'industry' => 'Events', 'description' => 'Brand ambassador and promotional work'],
            ['name' => 'Concert/Festival Staff', 'industry' => 'Events', 'description' => 'Working at concerts and festivals'],
            ['name' => 'Trade Show Support', 'industry' => 'Events', 'description' => 'Supporting exhibitors at trade shows'],

            // Security
            ['name' => 'Security Guard', 'industry' => 'Security', 'description' => 'Providing security guard services'],
            ['name' => 'Access Control', 'industry' => 'Security', 'description' => 'Managing facility access and entry points'],
            ['name' => 'Surveillance Monitoring', 'industry' => 'Security', 'description' => 'Monitoring security cameras and systems'],
            ['name' => 'Patrol Services', 'industry' => 'Security', 'description' => 'Conducting security patrols'],
            ['name' => 'Emergency Response', 'industry' => 'Security', 'description' => 'Responding to security emergencies'],
            ['name' => 'Report Writing', 'industry' => 'Security', 'description' => 'Writing security incident reports'],
            ['name' => 'Metal Detection', 'industry' => 'Security', 'description' => 'Operating metal detectors and screening'],
            ['name' => 'VIP Protection', 'industry' => 'Security', 'description' => 'Executive and VIP protection services'],

            // Cleaning / Janitorial
            ['name' => 'Janitorial Services', 'industry' => 'Cleaning', 'description' => 'General cleaning and janitorial work'],
            ['name' => 'Floor Care', 'industry' => 'Cleaning', 'description' => 'Floor cleaning, waxing, and maintenance'],
            ['name' => 'Window Cleaning', 'industry' => 'Cleaning', 'description' => 'Interior and exterior window cleaning'],
            ['name' => 'Deep Cleaning', 'industry' => 'Cleaning', 'description' => 'Thorough deep cleaning services'],
            ['name' => 'Sanitization', 'industry' => 'Cleaning', 'description' => 'Sanitizing and disinfecting surfaces'],
            ['name' => 'Carpet Cleaning', 'industry' => 'Cleaning', 'description' => 'Carpet cleaning and shampooing'],
            ['name' => 'Pressure Washing', 'industry' => 'Cleaning', 'description' => 'Using pressure washers for cleaning'],
            ['name' => 'Waste Management', 'industry' => 'Cleaning', 'description' => 'Managing trash and waste disposal'],

            // Administrative / Office
            ['name' => 'Data Entry', 'industry' => 'Administrative', 'description' => 'Entering data into computer systems'],
            ['name' => 'Reception', 'industry' => 'Administrative', 'description' => 'Front desk and reception duties'],
            ['name' => 'Phone Handling', 'industry' => 'Administrative', 'description' => 'Answering and routing phone calls'],
            ['name' => 'Filing and Organization', 'industry' => 'Administrative', 'description' => 'Organizing files and documents'],
            ['name' => 'Microsoft Office', 'industry' => 'Administrative', 'description' => 'Proficiency in MS Office suite'],
            ['name' => 'Google Workspace', 'industry' => 'Administrative', 'description' => 'Proficiency in Google Workspace apps'],
            ['name' => 'Scheduling', 'industry' => 'Administrative', 'description' => 'Managing calendars and appointments'],
            ['name' => 'Email Management', 'industry' => 'Administrative', 'description' => 'Managing email correspondence'],
            ['name' => 'Document Preparation', 'industry' => 'Administrative', 'description' => 'Creating and formatting documents'],

            // Manufacturing / Production
            ['name' => 'Assembly Line', 'industry' => 'Manufacturing', 'description' => 'Working on assembly lines'],
            ['name' => 'Machine Operation', 'industry' => 'Manufacturing', 'description' => 'Operating manufacturing machinery'],
            ['name' => 'Quality Inspection', 'industry' => 'Manufacturing', 'description' => 'Inspecting products for quality'],
            ['name' => 'CNC Operation', 'industry' => 'Manufacturing', 'description' => 'Operating CNC machines'],
            ['name' => 'Welding', 'industry' => 'Manufacturing', 'description' => 'Welding and metal fabrication'],
            ['name' => 'Soldering', 'industry' => 'Manufacturing', 'description' => 'Electronic soldering and assembly'],
            ['name' => 'Packaging Line', 'industry' => 'Manufacturing', 'description' => 'Working on packaging lines'],
            ['name' => 'Lean Manufacturing', 'industry' => 'Manufacturing', 'description' => 'Lean manufacturing principles'],

            // Construction / Trades
            ['name' => 'General Labor', 'industry' => 'Construction', 'description' => 'General construction labor'],
            ['name' => 'Carpentry', 'industry' => 'Construction', 'description' => 'Wood framing and carpentry work'],
            ['name' => 'Painting', 'industry' => 'Construction', 'description' => 'Interior and exterior painting'],
            ['name' => 'Drywall Installation', 'industry' => 'Construction', 'description' => 'Installing and finishing drywall'],
            ['name' => 'Electrical Work', 'industry' => 'Construction', 'description' => 'Basic electrical installation'],
            ['name' => 'Plumbing', 'industry' => 'Construction', 'description' => 'Basic plumbing work'],
            ['name' => 'HVAC', 'industry' => 'Construction', 'description' => 'Heating and cooling systems'],
            ['name' => 'Demolition', 'industry' => 'Construction', 'description' => 'Demolition and site clearing'],
            ['name' => 'Landscaping', 'industry' => 'Construction', 'description' => 'Landscaping and groundskeeping'],
            ['name' => 'Concrete Work', 'industry' => 'Construction', 'description' => 'Pouring and finishing concrete'],

            // Driving / Transportation
            ['name' => 'Delivery Driving', 'industry' => 'Transportation', 'description' => 'Delivering packages and goods'],
            ['name' => 'Commercial Driving', 'industry' => 'Transportation', 'description' => 'Driving commercial vehicles'],
            ['name' => 'Route Planning', 'industry' => 'Transportation', 'description' => 'Planning efficient delivery routes'],
            ['name' => 'Vehicle Inspection', 'industry' => 'Transportation', 'description' => 'Pre-trip vehicle inspections'],
            ['name' => 'GPS Navigation', 'industry' => 'Transportation', 'description' => 'Using GPS for navigation'],
            ['name' => 'Passenger Transport', 'industry' => 'Transportation', 'description' => 'Transporting passengers safely'],
            ['name' => 'Truck Loading', 'industry' => 'Transportation', 'description' => 'Loading and securing cargo'],

            // Technology
            ['name' => 'Technical Support', 'industry' => 'Technology', 'description' => 'IT technical support and troubleshooting'],
            ['name' => 'Network Setup', 'industry' => 'Technology', 'description' => 'Setting up network equipment'],
            ['name' => 'Hardware Installation', 'industry' => 'Technology', 'description' => 'Installing computer hardware'],
            ['name' => 'Software Installation', 'industry' => 'Technology', 'description' => 'Installing and configuring software'],
            ['name' => 'Cable Management', 'industry' => 'Technology', 'description' => 'Organizing and managing cables'],
        ];

        // Use updateOrCreate to avoid duplicates
        foreach ($skills as $skill) {
            Skill::updateOrCreate(
                ['name' => $skill['name']],
                $skill
            );
        }

        // Count by industry
        $industries = collect($skills)->groupBy('industry')->map->count();

        $this->command->info('Skills seeded: ' . count($skills));
        $this->command->info('Skills by industry:');
        foreach ($industries as $industry => $count) {
            $this->command->info("  - {$industry}: {$count}");
        }
    }
}
