<div
    class="w-full max-w-5xl mx-auto overflow-hidden rounded-lg border border-slate-800 bg-slate-950 shadow-2xl font-mono text-xs md:text-sm">
    {{-- Terminal Header --}}
    <div class="flex items-center justify-between bg-slate-900 border-b border-slate-800 px-4 py-2">
        <div class="flex items-center gap-2">
            <div class="relative flex h-2 w-2">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </div>
            <span class="font-bold text-emerald-500 tracking-wider">LIVE MARKET DATA</span>
        </div>
        <div class="flex items-center gap-4 text-slate-500">
            <span>INDEX: <span class="text-emerald-500">▲ 1.2%</span></span>
            <span>VOL: <span class="text-blue-400">24.5K</span></span>
            <span>UTC {{ date('H:i:s') }}</span>
        </div>
    </div>

    {{-- Ticker Tape (Scrolls Horizontally) --}}
    <div class="relative flex overflow-x-hidden bg-slate-900/50 border-b border-slate-800 py-1">
        <div class="animate-marquee whitespace-nowrap flex gap-8 text-slate-400">
            <span>NYC-RN <span class="text-emerald-500">$85.00</span></span>
            <span>LAX-CNA <span class="text-emerald-500">$42.50</span></span>
            <span>CHI-LPN <span class="text-emerald-500">$55.00</span></span>
            <span>MIA-SRVR <span class="text-emerald-500">$28.00</span></span>
            <span>DAL-CHEF <span class="text-emerald-500">$35.00</span></span>
            <span>SEA-WH <span class="text-emerald-500">$22.00</span></span>
            <span>BOS-RN <span class="text-emerald-500">$88.00</span></span>
            <span>SF-DEV <span class="text-emerald-500">$120.00</span></span>
            {{-- Duplicates for scrolling --}}
            <span>NYC-RN <span class="text-emerald-500">$85.00</span></span>
            <span>LAX-CNA <span class="text-emerald-500">$42.50</span></span>
            <span>CHI-LPN <span class="text-emerald-500">$55.00</span></span>
            <span>MIA-SRVR <span class="text-emerald-500">$28.00</span></span>
        </div>
    </div>

    {{-- Main Board --}}
    <div class="p-1 bg-slate-950">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 border-b border-slate-800/50">
                    <th class="p-2 font-normal">SYMBOL</th>
                    <th class="p-2 font-normal">ROLE</th>
                    <th class="p-2 font-normal hidden sm:table-cell">LOCATION</th>
                    <th class="p-2 font-normal text-right">BID (RATE)</th>
                    <th class="p-2 font-normal text-right hidden sm:table-cell">VOL (HRS)</th>
                    <th class="p-2 font-normal text-right">CHG</th>
                    <th class="p-2 font-normal text-right">ACTION</th>
                </tr>
            </thead>
            <tbody id="market-board-body" class="text-slate-300">
                {{-- Data Rows (Simulated) --}}
                @php
                    $demodata = [
                        ['sym' => 'NYC-RN', 'role' => 'Registered Nurse', 'loc' => 'New York, NY', 'rate' => 85.00, 'vol' => 12, 'chg' => 2.5],
                        ['sym' => 'LAX-CNA', 'role' => 'Nursing Assistant', 'loc' => 'Los Angeles, CA', 'rate' => 42.50, 'vol' => 8, 'chg' => 0.5],
                        ['sym' => 'CHI-LPN', 'role' => 'Practical Nurse', 'loc' => 'Chicago, IL', 'rate' => 55.00, 'vol' => 12, 'chg' => 1.2],
                        ['sym' => 'MIA-SRV', 'role' => 'Banquet Server', 'loc' => 'Miami, FL', 'rate' => 28.00, 'vol' => 6, 'chg' => -0.5],
                        ['sym' => 'DAL-CHF', 'role' => 'Sous Chef', 'loc' => 'Dallas, TX', 'rate' => 35.00, 'vol' => 10, 'chg' => 0.0],
                        ['sym' => 'SEA-WH', 'role' => 'Warehouse Op', 'loc' => 'Seattle, WA', 'rate' => 22.00, 'vol' => 8, 'chg' => 0.2],
                        ['sym' => 'AUS-BAR', 'role' => 'Bartender', 'loc' => 'Austin, TX', 'rate' => 25.00, 'vol' => 8, 'chg' => 1.5],
                        ['sym' => 'DEN-SEC', 'role' => 'Security Officer', 'loc' => 'Denver, CO', 'rate' => 24.00, 'vol' => 12, 'chg' => 0.0],
                    ];
                @endphp
                @foreach($demodata as $row)
                    <tr class="border-b border-slate-800/30 hover:bg-slate-900 transition-colors group cursor-default">
                        <td class="p-2 font-bold text-blue-400 group-hover:text-blue-300">{{ $row['sym'] }}</td>
                        <td class="p-2 truncate max-w-[120px]">{{ $row['role'] }}</td>
                        <td class="p-2 hidden sm:table-cell text-slate-500">{{ $row['loc'] }}</td>
                        <td class="p-2 text-right font-bold text-emerald-400">${{ number_format($row['rate'], 2) }}</td>
                        <td class="p-2 text-right hidden sm:table-cell text-slate-400">{{ $row['vol'] }}</td>
                        <td class="p-2 text-right {{ $row['chg'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                            {{ $row['chg'] >= 0 ? '+' : '' }}{{ $row['chg'] }}%
                        </td>
                        <td class="p-2 text-right">
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center h-6 px-3 text-xs font-bold text-slate-900 bg-emerald-500 hover:bg-emerald-400 rounded transition-colors uppercase tracking-wide">
                                Take Shift
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Footer Status --}}
    <div class="bg-slate-900 border-t border-slate-800 px-4 py-1 flex justify-between text-xs text-slate-600">
        <span>MARKET STATUS: <span class="text-emerald-500 font-bold">OPEN</span></span>
        <span>CONNECTED: <span class="text-blue-400">Low Latency (12ms)</span></span>
    </div>
</div>

<style>
    @keyframes marquee {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    .animate-marquee {
        animation: marquee 20s linear infinite;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.querySelectorAll('#market-board-body tr');

        // Randomly "update" rows to simulate live data
        setInterval(() => {
            const rowIndex = Math.floor(Math.random() * rows.length);
            const row = rows[rowIndex];

            // Flash effect
            row.classList.add('bg-slate-800');
            setTimeout(() => row.classList.remove('bg-slate-800'), 300);

            // Randomly update rate slightly
            const rateCell = row.querySelector('td:nth-child(4)');
            if (rateCell) {
                let currentRate = parseFloat(rateCell.innerText.replace('$', ''));
                let change = (Math.random() - 0.5) * 2; // -1 to +1
                let newRate = Math.max(15, currentRate + change);

                // Color flash based on direction
                rateCell.className = `p-2 text-right font-bold ${change >= 0 ? 'text-emerald-400' : 'text-red-400'}`;
                rateCell.innerText = '$' + newRate.toFixed(2);

                // Reset color after delay
                setTimeout(() => {
                    rateCell.className = `p-2 text-right font-bold text-emerald-400`;
                }, 1000);
            }
        }, 800);
    });
</script>