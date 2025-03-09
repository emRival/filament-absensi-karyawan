<div>
    <div class="container mx-auto">
        <div class="bg-white p-6 mt-3 rounded-lg shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Informasi Pegawai</h2>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <p><strong>Nama Pegawai : </strong> {{ Auth::user()->name }}</p>
                        <p><strong>Kantor : </strong>{{ $schedule->office->name }}</p>
                        <p><strong>Shift : </strong>{{ $schedule->shift->name }} ({{ $schedule->shift->start_time }} -
                            {{ $schedule->shift->end_time }} WIB)</p>

                    </div>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-2">Presensi</h2>
                    <div id="map" class="mb-4 rounded-lg border border-gray-500"></div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md">Tag Location</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}], 18);
        const tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

        L.tileLayer(tileUrl, {
            minZoom: 1,
            maxZoom: 22,
            ext: 'png',
        }).addTo(map);

        const office = [{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}];
        const radius = {{ $schedule->office->radius }};
        const circle = L.circle(office, {
            color: 'red',
            fillColor: '#f05',
            fillOpacity: 0.5,
            radius: radius
        }).addTo(map);
    </script>
</div>
