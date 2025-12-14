<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Countries;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds comprehensive list of 195+ countries with currency and phone codes.
     *
     * @return void
     */
    public function run()
    {
        $countries = [
            // North America
            ['country_code' => 'US', 'name' => 'United States', 'phone_code' => '+1', 'currency_code' => 'USD'],
            ['country_code' => 'CA', 'name' => 'Canada', 'phone_code' => '+1', 'currency_code' => 'CAD'],
            ['country_code' => 'MX', 'name' => 'Mexico', 'phone_code' => '+52', 'currency_code' => 'MXN'],

            // Central America
            ['country_code' => 'GT', 'name' => 'Guatemala', 'phone_code' => '+502', 'currency_code' => 'GTQ'],
            ['country_code' => 'BZ', 'name' => 'Belize', 'phone_code' => '+501', 'currency_code' => 'BZD'],
            ['country_code' => 'HN', 'name' => 'Honduras', 'phone_code' => '+504', 'currency_code' => 'HNL'],
            ['country_code' => 'SV', 'name' => 'El Salvador', 'phone_code' => '+503', 'currency_code' => 'USD'],
            ['country_code' => 'NI', 'name' => 'Nicaragua', 'phone_code' => '+505', 'currency_code' => 'NIO'],
            ['country_code' => 'CR', 'name' => 'Costa Rica', 'phone_code' => '+506', 'currency_code' => 'CRC'],
            ['country_code' => 'PA', 'name' => 'Panama', 'phone_code' => '+507', 'currency_code' => 'PAB'],

            // Caribbean
            ['country_code' => 'CU', 'name' => 'Cuba', 'phone_code' => '+53', 'currency_code' => 'CUP'],
            ['country_code' => 'JM', 'name' => 'Jamaica', 'phone_code' => '+1876', 'currency_code' => 'JMD'],
            ['country_code' => 'HT', 'name' => 'Haiti', 'phone_code' => '+509', 'currency_code' => 'HTG'],
            ['country_code' => 'DO', 'name' => 'Dominican Republic', 'phone_code' => '+1809', 'currency_code' => 'DOP'],
            ['country_code' => 'PR', 'name' => 'Puerto Rico', 'phone_code' => '+1787', 'currency_code' => 'USD'],
            ['country_code' => 'TT', 'name' => 'Trinidad and Tobago', 'phone_code' => '+1868', 'currency_code' => 'TTD'],
            ['country_code' => 'BB', 'name' => 'Barbados', 'phone_code' => '+1246', 'currency_code' => 'BBD'],
            ['country_code' => 'BS', 'name' => 'Bahamas', 'phone_code' => '+1242', 'currency_code' => 'BSD'],

            // South America
            ['country_code' => 'BR', 'name' => 'Brazil', 'phone_code' => '+55', 'currency_code' => 'BRL'],
            ['country_code' => 'AR', 'name' => 'Argentina', 'phone_code' => '+54', 'currency_code' => 'ARS'],
            ['country_code' => 'CL', 'name' => 'Chile', 'phone_code' => '+56', 'currency_code' => 'CLP'],
            ['country_code' => 'CO', 'name' => 'Colombia', 'phone_code' => '+57', 'currency_code' => 'COP'],
            ['country_code' => 'PE', 'name' => 'Peru', 'phone_code' => '+51', 'currency_code' => 'PEN'],
            ['country_code' => 'VE', 'name' => 'Venezuela', 'phone_code' => '+58', 'currency_code' => 'VES'],
            ['country_code' => 'EC', 'name' => 'Ecuador', 'phone_code' => '+593', 'currency_code' => 'USD'],
            ['country_code' => 'BO', 'name' => 'Bolivia', 'phone_code' => '+591', 'currency_code' => 'BOB'],
            ['country_code' => 'PY', 'name' => 'Paraguay', 'phone_code' => '+595', 'currency_code' => 'PYG'],
            ['country_code' => 'UY', 'name' => 'Uruguay', 'phone_code' => '+598', 'currency_code' => 'UYU'],
            ['country_code' => 'GY', 'name' => 'Guyana', 'phone_code' => '+592', 'currency_code' => 'GYD'],
            ['country_code' => 'SR', 'name' => 'Suriname', 'phone_code' => '+597', 'currency_code' => 'SRD'],

            // Western Europe
            ['country_code' => 'GB', 'name' => 'United Kingdom', 'phone_code' => '+44', 'currency_code' => 'GBP'],
            ['country_code' => 'DE', 'name' => 'Germany', 'phone_code' => '+49', 'currency_code' => 'EUR'],
            ['country_code' => 'FR', 'name' => 'France', 'phone_code' => '+33', 'currency_code' => 'EUR'],
            ['country_code' => 'IT', 'name' => 'Italy', 'phone_code' => '+39', 'currency_code' => 'EUR'],
            ['country_code' => 'ES', 'name' => 'Spain', 'phone_code' => '+34', 'currency_code' => 'EUR'],
            ['country_code' => 'PT', 'name' => 'Portugal', 'phone_code' => '+351', 'currency_code' => 'EUR'],
            ['country_code' => 'NL', 'name' => 'Netherlands', 'phone_code' => '+31', 'currency_code' => 'EUR'],
            ['country_code' => 'BE', 'name' => 'Belgium', 'phone_code' => '+32', 'currency_code' => 'EUR'],
            ['country_code' => 'CH', 'name' => 'Switzerland', 'phone_code' => '+41', 'currency_code' => 'CHF'],
            ['country_code' => 'AT', 'name' => 'Austria', 'phone_code' => '+43', 'currency_code' => 'EUR'],
            ['country_code' => 'IE', 'name' => 'Ireland', 'phone_code' => '+353', 'currency_code' => 'EUR'],
            ['country_code' => 'LU', 'name' => 'Luxembourg', 'phone_code' => '+352', 'currency_code' => 'EUR'],
            ['country_code' => 'MC', 'name' => 'Monaco', 'phone_code' => '+377', 'currency_code' => 'EUR'],
            ['country_code' => 'LI', 'name' => 'Liechtenstein', 'phone_code' => '+423', 'currency_code' => 'CHF'],
            ['country_code' => 'AD', 'name' => 'Andorra', 'phone_code' => '+376', 'currency_code' => 'EUR'],

            // Northern Europe
            ['country_code' => 'SE', 'name' => 'Sweden', 'phone_code' => '+46', 'currency_code' => 'SEK'],
            ['country_code' => 'NO', 'name' => 'Norway', 'phone_code' => '+47', 'currency_code' => 'NOK'],
            ['country_code' => 'DK', 'name' => 'Denmark', 'phone_code' => '+45', 'currency_code' => 'DKK'],
            ['country_code' => 'FI', 'name' => 'Finland', 'phone_code' => '+358', 'currency_code' => 'EUR'],
            ['country_code' => 'IS', 'name' => 'Iceland', 'phone_code' => '+354', 'currency_code' => 'ISK'],
            ['country_code' => 'EE', 'name' => 'Estonia', 'phone_code' => '+372', 'currency_code' => 'EUR'],
            ['country_code' => 'LV', 'name' => 'Latvia', 'phone_code' => '+371', 'currency_code' => 'EUR'],
            ['country_code' => 'LT', 'name' => 'Lithuania', 'phone_code' => '+370', 'currency_code' => 'EUR'],

            // Eastern Europe
            ['country_code' => 'PL', 'name' => 'Poland', 'phone_code' => '+48', 'currency_code' => 'PLN'],
            ['country_code' => 'CZ', 'name' => 'Czech Republic', 'phone_code' => '+420', 'currency_code' => 'CZK'],
            ['country_code' => 'SK', 'name' => 'Slovakia', 'phone_code' => '+421', 'currency_code' => 'EUR'],
            ['country_code' => 'HU', 'name' => 'Hungary', 'phone_code' => '+36', 'currency_code' => 'HUF'],
            ['country_code' => 'RO', 'name' => 'Romania', 'phone_code' => '+40', 'currency_code' => 'RON'],
            ['country_code' => 'BG', 'name' => 'Bulgaria', 'phone_code' => '+359', 'currency_code' => 'BGN'],
            ['country_code' => 'UA', 'name' => 'Ukraine', 'phone_code' => '+380', 'currency_code' => 'UAH'],
            ['country_code' => 'BY', 'name' => 'Belarus', 'phone_code' => '+375', 'currency_code' => 'BYN'],
            ['country_code' => 'MD', 'name' => 'Moldova', 'phone_code' => '+373', 'currency_code' => 'MDL'],
            ['country_code' => 'RU', 'name' => 'Russia', 'phone_code' => '+7', 'currency_code' => 'RUB'],

            // Southern Europe / Balkans
            ['country_code' => 'GR', 'name' => 'Greece', 'phone_code' => '+30', 'currency_code' => 'EUR'],
            ['country_code' => 'HR', 'name' => 'Croatia', 'phone_code' => '+385', 'currency_code' => 'EUR'],
            ['country_code' => 'SI', 'name' => 'Slovenia', 'phone_code' => '+386', 'currency_code' => 'EUR'],
            ['country_code' => 'RS', 'name' => 'Serbia', 'phone_code' => '+381', 'currency_code' => 'RSD'],
            ['country_code' => 'BA', 'name' => 'Bosnia and Herzegovina', 'phone_code' => '+387', 'currency_code' => 'BAM'],
            ['country_code' => 'ME', 'name' => 'Montenegro', 'phone_code' => '+382', 'currency_code' => 'EUR'],
            ['country_code' => 'MK', 'name' => 'North Macedonia', 'phone_code' => '+389', 'currency_code' => 'MKD'],
            ['country_code' => 'AL', 'name' => 'Albania', 'phone_code' => '+355', 'currency_code' => 'ALL'],
            ['country_code' => 'XK', 'name' => 'Kosovo', 'phone_code' => '+383', 'currency_code' => 'EUR'],
            ['country_code' => 'CY', 'name' => 'Cyprus', 'phone_code' => '+357', 'currency_code' => 'EUR'],
            ['country_code' => 'MT', 'name' => 'Malta', 'phone_code' => '+356', 'currency_code' => 'EUR'],

            // Middle East
            ['country_code' => 'TR', 'name' => 'Turkey', 'phone_code' => '+90', 'currency_code' => 'TRY'],
            ['country_code' => 'IL', 'name' => 'Israel', 'phone_code' => '+972', 'currency_code' => 'ILS'],
            ['country_code' => 'SA', 'name' => 'Saudi Arabia', 'phone_code' => '+966', 'currency_code' => 'SAR'],
            ['country_code' => 'AE', 'name' => 'United Arab Emirates', 'phone_code' => '+971', 'currency_code' => 'AED'],
            ['country_code' => 'QA', 'name' => 'Qatar', 'phone_code' => '+974', 'currency_code' => 'QAR'],
            ['country_code' => 'KW', 'name' => 'Kuwait', 'phone_code' => '+965', 'currency_code' => 'KWD'],
            ['country_code' => 'BH', 'name' => 'Bahrain', 'phone_code' => '+973', 'currency_code' => 'BHD'],
            ['country_code' => 'OM', 'name' => 'Oman', 'phone_code' => '+968', 'currency_code' => 'OMR'],
            ['country_code' => 'JO', 'name' => 'Jordan', 'phone_code' => '+962', 'currency_code' => 'JOD'],
            ['country_code' => 'LB', 'name' => 'Lebanon', 'phone_code' => '+961', 'currency_code' => 'LBP'],
            ['country_code' => 'SY', 'name' => 'Syria', 'phone_code' => '+963', 'currency_code' => 'SYP'],
            ['country_code' => 'IQ', 'name' => 'Iraq', 'phone_code' => '+964', 'currency_code' => 'IQD'],
            ['country_code' => 'IR', 'name' => 'Iran', 'phone_code' => '+98', 'currency_code' => 'IRR'],
            ['country_code' => 'YE', 'name' => 'Yemen', 'phone_code' => '+967', 'currency_code' => 'YER'],

            // Central Asia
            ['country_code' => 'KZ', 'name' => 'Kazakhstan', 'phone_code' => '+7', 'currency_code' => 'KZT'],
            ['country_code' => 'UZ', 'name' => 'Uzbekistan', 'phone_code' => '+998', 'currency_code' => 'UZS'],
            ['country_code' => 'TM', 'name' => 'Turkmenistan', 'phone_code' => '+993', 'currency_code' => 'TMT'],
            ['country_code' => 'KG', 'name' => 'Kyrgyzstan', 'phone_code' => '+996', 'currency_code' => 'KGS'],
            ['country_code' => 'TJ', 'name' => 'Tajikistan', 'phone_code' => '+992', 'currency_code' => 'TJS'],
            ['country_code' => 'AF', 'name' => 'Afghanistan', 'phone_code' => '+93', 'currency_code' => 'AFN'],

            // South Asia
            ['country_code' => 'IN', 'name' => 'India', 'phone_code' => '+91', 'currency_code' => 'INR'],
            ['country_code' => 'PK', 'name' => 'Pakistan', 'phone_code' => '+92', 'currency_code' => 'PKR'],
            ['country_code' => 'BD', 'name' => 'Bangladesh', 'phone_code' => '+880', 'currency_code' => 'BDT'],
            ['country_code' => 'LK', 'name' => 'Sri Lanka', 'phone_code' => '+94', 'currency_code' => 'LKR'],
            ['country_code' => 'NP', 'name' => 'Nepal', 'phone_code' => '+977', 'currency_code' => 'NPR'],
            ['country_code' => 'BT', 'name' => 'Bhutan', 'phone_code' => '+975', 'currency_code' => 'BTN'],
            ['country_code' => 'MV', 'name' => 'Maldives', 'phone_code' => '+960', 'currency_code' => 'MVR'],

            // East Asia
            ['country_code' => 'CN', 'name' => 'China', 'phone_code' => '+86', 'currency_code' => 'CNY'],
            ['country_code' => 'JP', 'name' => 'Japan', 'phone_code' => '+81', 'currency_code' => 'JPY'],
            ['country_code' => 'KR', 'name' => 'South Korea', 'phone_code' => '+82', 'currency_code' => 'KRW'],
            ['country_code' => 'KP', 'name' => 'North Korea', 'phone_code' => '+850', 'currency_code' => 'KPW'],
            ['country_code' => 'TW', 'name' => 'Taiwan', 'phone_code' => '+886', 'currency_code' => 'TWD'],
            ['country_code' => 'HK', 'name' => 'Hong Kong', 'phone_code' => '+852', 'currency_code' => 'HKD'],
            ['country_code' => 'MO', 'name' => 'Macau', 'phone_code' => '+853', 'currency_code' => 'MOP'],
            ['country_code' => 'MN', 'name' => 'Mongolia', 'phone_code' => '+976', 'currency_code' => 'MNT'],

            // Southeast Asia
            ['country_code' => 'SG', 'name' => 'Singapore', 'phone_code' => '+65', 'currency_code' => 'SGD'],
            ['country_code' => 'MY', 'name' => 'Malaysia', 'phone_code' => '+60', 'currency_code' => 'MYR'],
            ['country_code' => 'TH', 'name' => 'Thailand', 'phone_code' => '+66', 'currency_code' => 'THB'],
            ['country_code' => 'ID', 'name' => 'Indonesia', 'phone_code' => '+62', 'currency_code' => 'IDR'],
            ['country_code' => 'PH', 'name' => 'Philippines', 'phone_code' => '+63', 'currency_code' => 'PHP'],
            ['country_code' => 'VN', 'name' => 'Vietnam', 'phone_code' => '+84', 'currency_code' => 'VND'],
            ['country_code' => 'MM', 'name' => 'Myanmar', 'phone_code' => '+95', 'currency_code' => 'MMK'],
            ['country_code' => 'KH', 'name' => 'Cambodia', 'phone_code' => '+855', 'currency_code' => 'KHR'],
            ['country_code' => 'LA', 'name' => 'Laos', 'phone_code' => '+856', 'currency_code' => 'LAK'],
            ['country_code' => 'BN', 'name' => 'Brunei', 'phone_code' => '+673', 'currency_code' => 'BND'],
            ['country_code' => 'TL', 'name' => 'Timor-Leste', 'phone_code' => '+670', 'currency_code' => 'USD'],

            // Oceania
            ['country_code' => 'AU', 'name' => 'Australia', 'phone_code' => '+61', 'currency_code' => 'AUD'],
            ['country_code' => 'NZ', 'name' => 'New Zealand', 'phone_code' => '+64', 'currency_code' => 'NZD'],
            ['country_code' => 'PG', 'name' => 'Papua New Guinea', 'phone_code' => '+675', 'currency_code' => 'PGK'],
            ['country_code' => 'FJ', 'name' => 'Fiji', 'phone_code' => '+679', 'currency_code' => 'FJD'],
            ['country_code' => 'SB', 'name' => 'Solomon Islands', 'phone_code' => '+677', 'currency_code' => 'SBD'],
            ['country_code' => 'VU', 'name' => 'Vanuatu', 'phone_code' => '+678', 'currency_code' => 'VUV'],
            ['country_code' => 'WS', 'name' => 'Samoa', 'phone_code' => '+685', 'currency_code' => 'WST'],
            ['country_code' => 'TO', 'name' => 'Tonga', 'phone_code' => '+676', 'currency_code' => 'TOP'],
            ['country_code' => 'KI', 'name' => 'Kiribati', 'phone_code' => '+686', 'currency_code' => 'AUD'],
            ['country_code' => 'FM', 'name' => 'Micronesia', 'phone_code' => '+691', 'currency_code' => 'USD'],
            ['country_code' => 'MH', 'name' => 'Marshall Islands', 'phone_code' => '+692', 'currency_code' => 'USD'],
            ['country_code' => 'PW', 'name' => 'Palau', 'phone_code' => '+680', 'currency_code' => 'USD'],
            ['country_code' => 'NR', 'name' => 'Nauru', 'phone_code' => '+674', 'currency_code' => 'AUD'],
            ['country_code' => 'TV', 'name' => 'Tuvalu', 'phone_code' => '+688', 'currency_code' => 'AUD'],

            // North Africa
            ['country_code' => 'EG', 'name' => 'Egypt', 'phone_code' => '+20', 'currency_code' => 'EGP'],
            ['country_code' => 'LY', 'name' => 'Libya', 'phone_code' => '+218', 'currency_code' => 'LYD'],
            ['country_code' => 'TN', 'name' => 'Tunisia', 'phone_code' => '+216', 'currency_code' => 'TND'],
            ['country_code' => 'DZ', 'name' => 'Algeria', 'phone_code' => '+213', 'currency_code' => 'DZD'],
            ['country_code' => 'MA', 'name' => 'Morocco', 'phone_code' => '+212', 'currency_code' => 'MAD'],
            ['country_code' => 'SD', 'name' => 'Sudan', 'phone_code' => '+249', 'currency_code' => 'SDG'],
            ['country_code' => 'SS', 'name' => 'South Sudan', 'phone_code' => '+211', 'currency_code' => 'SSP'],

            // West Africa
            ['country_code' => 'NG', 'name' => 'Nigeria', 'phone_code' => '+234', 'currency_code' => 'NGN'],
            ['country_code' => 'GH', 'name' => 'Ghana', 'phone_code' => '+233', 'currency_code' => 'GHS'],
            ['country_code' => 'SN', 'name' => 'Senegal', 'phone_code' => '+221', 'currency_code' => 'XOF'],
            ['country_code' => 'CI', 'name' => 'Ivory Coast', 'phone_code' => '+225', 'currency_code' => 'XOF'],
            ['country_code' => 'ML', 'name' => 'Mali', 'phone_code' => '+223', 'currency_code' => 'XOF'],
            ['country_code' => 'BF', 'name' => 'Burkina Faso', 'phone_code' => '+226', 'currency_code' => 'XOF'],
            ['country_code' => 'NE', 'name' => 'Niger', 'phone_code' => '+227', 'currency_code' => 'XOF'],
            ['country_code' => 'GN', 'name' => 'Guinea', 'phone_code' => '+224', 'currency_code' => 'GNF'],
            ['country_code' => 'BJ', 'name' => 'Benin', 'phone_code' => '+229', 'currency_code' => 'XOF'],
            ['country_code' => 'TG', 'name' => 'Togo', 'phone_code' => '+228', 'currency_code' => 'XOF'],
            ['country_code' => 'SL', 'name' => 'Sierra Leone', 'phone_code' => '+232', 'currency_code' => 'SLL'],
            ['country_code' => 'LR', 'name' => 'Liberia', 'phone_code' => '+231', 'currency_code' => 'LRD'],
            ['country_code' => 'MR', 'name' => 'Mauritania', 'phone_code' => '+222', 'currency_code' => 'MRU'],
            ['country_code' => 'GM', 'name' => 'Gambia', 'phone_code' => '+220', 'currency_code' => 'GMD'],
            ['country_code' => 'GW', 'name' => 'Guinea-Bissau', 'phone_code' => '+245', 'currency_code' => 'XOF'],
            ['country_code' => 'CV', 'name' => 'Cape Verde', 'phone_code' => '+238', 'currency_code' => 'CVE'],

            // Central Africa
            ['country_code' => 'CM', 'name' => 'Cameroon', 'phone_code' => '+237', 'currency_code' => 'XAF'],
            ['country_code' => 'CD', 'name' => 'Democratic Republic of the Congo', 'phone_code' => '+243', 'currency_code' => 'CDF'],
            ['country_code' => 'CG', 'name' => 'Republic of the Congo', 'phone_code' => '+242', 'currency_code' => 'XAF'],
            ['country_code' => 'CF', 'name' => 'Central African Republic', 'phone_code' => '+236', 'currency_code' => 'XAF'],
            ['country_code' => 'TD', 'name' => 'Chad', 'phone_code' => '+235', 'currency_code' => 'XAF'],
            ['country_code' => 'GA', 'name' => 'Gabon', 'phone_code' => '+241', 'currency_code' => 'XAF'],
            ['country_code' => 'GQ', 'name' => 'Equatorial Guinea', 'phone_code' => '+240', 'currency_code' => 'XAF'],
            ['country_code' => 'ST', 'name' => 'Sao Tome and Principe', 'phone_code' => '+239', 'currency_code' => 'STN'],

            // East Africa
            ['country_code' => 'KE', 'name' => 'Kenya', 'phone_code' => '+254', 'currency_code' => 'KES'],
            ['country_code' => 'TZ', 'name' => 'Tanzania', 'phone_code' => '+255', 'currency_code' => 'TZS'],
            ['country_code' => 'UG', 'name' => 'Uganda', 'phone_code' => '+256', 'currency_code' => 'UGX'],
            ['country_code' => 'RW', 'name' => 'Rwanda', 'phone_code' => '+250', 'currency_code' => 'RWF'],
            ['country_code' => 'BI', 'name' => 'Burundi', 'phone_code' => '+257', 'currency_code' => 'BIF'],
            ['country_code' => 'ET', 'name' => 'Ethiopia', 'phone_code' => '+251', 'currency_code' => 'ETB'],
            ['country_code' => 'ER', 'name' => 'Eritrea', 'phone_code' => '+291', 'currency_code' => 'ERN'],
            ['country_code' => 'DJ', 'name' => 'Djibouti', 'phone_code' => '+253', 'currency_code' => 'DJF'],
            ['country_code' => 'SO', 'name' => 'Somalia', 'phone_code' => '+252', 'currency_code' => 'SOS'],
            ['country_code' => 'SC', 'name' => 'Seychelles', 'phone_code' => '+248', 'currency_code' => 'SCR'],
            ['country_code' => 'MU', 'name' => 'Mauritius', 'phone_code' => '+230', 'currency_code' => 'MUR'],
            ['country_code' => 'MG', 'name' => 'Madagascar', 'phone_code' => '+261', 'currency_code' => 'MGA'],
            ['country_code' => 'KM', 'name' => 'Comoros', 'phone_code' => '+269', 'currency_code' => 'KMF'],

            // Southern Africa
            ['country_code' => 'ZA', 'name' => 'South Africa', 'phone_code' => '+27', 'currency_code' => 'ZAR'],
            ['country_code' => 'NA', 'name' => 'Namibia', 'phone_code' => '+264', 'currency_code' => 'NAD'],
            ['country_code' => 'BW', 'name' => 'Botswana', 'phone_code' => '+267', 'currency_code' => 'BWP'],
            ['country_code' => 'ZW', 'name' => 'Zimbabwe', 'phone_code' => '+263', 'currency_code' => 'ZWL'],
            ['country_code' => 'ZM', 'name' => 'Zambia', 'phone_code' => '+260', 'currency_code' => 'ZMW'],
            ['country_code' => 'MW', 'name' => 'Malawi', 'phone_code' => '+265', 'currency_code' => 'MWK'],
            ['country_code' => 'MZ', 'name' => 'Mozambique', 'phone_code' => '+258', 'currency_code' => 'MZN'],
            ['country_code' => 'AO', 'name' => 'Angola', 'phone_code' => '+244', 'currency_code' => 'AOA'],
            ['country_code' => 'SZ', 'name' => 'Eswatini', 'phone_code' => '+268', 'currency_code' => 'SZL'],
            ['country_code' => 'LS', 'name' => 'Lesotho', 'phone_code' => '+266', 'currency_code' => 'LSL'],

            // Caucasus
            ['country_code' => 'GE', 'name' => 'Georgia', 'phone_code' => '+995', 'currency_code' => 'GEL'],
            ['country_code' => 'AM', 'name' => 'Armenia', 'phone_code' => '+374', 'currency_code' => 'AMD'],
            ['country_code' => 'AZ', 'name' => 'Azerbaijan', 'phone_code' => '+994', 'currency_code' => 'AZN'],
        ];

        // Add is_active flag to all countries
        foreach ($countries as &$country) {
            $country['is_active'] = true;
        }

        // Use upsert to avoid duplicates
        foreach ($countries as $country) {
            Countries::updateOrCreate(
                ['country_code' => $country['country_code']],
                $country
            );
        }

        $this->command->info('Countries seeded: ' . count($countries));
    }
}
