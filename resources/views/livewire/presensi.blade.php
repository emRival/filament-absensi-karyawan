<div>
    @if (session()->has('message'))
        <div class="fixed top-0 left-1/2 transform -translate-x-1/2 mt-4" x-data="{ show: true }" x-show="show"
            x-init="setTimeout(() => show = false, 3000)">
            <div class="bg-green-500 text-white p-4 rounded-lg shadow-lg">
                {{ session('message') }}
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="fixed top-0 left-1/2 transform -translate-x-1/2 mt-4" x-data="{ show: true }" x-show="show"
            x-init="setTimeout(() => show = false, 3000)">
            <div class="bg-red-500 text-white p-4 rounded-lg shadow-lg">
                {{ session('error') }}
            </div>
        </div>
    @endif
    <div class="container mx-auto">

        <div class="bg-white p-6 mt-3 rounded-lg shadow-lg">
            <div class="flex justify-end  mb-2  ">
                <a href="/admin/attendances" class="px-4 py-2 bg-red-500 text-white rounded-md">Kembali</a>
            </div>
            <div class="grid sm:grid-cols-1 md:grid-cols-2  gap-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Informasi Pegawai</h2>
                    <table class="w-full bg-gray-100 p-4 rounded-lg">
                        <tr>
                            <td class="py-2 px-4 w-1/2"><strong>Nama Pegawai</strong></td>
                            <td class="py-2 px-4 w-1/2">: {{ Auth::user()->name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 w-1/2"><strong>Kantor</strong></td>
                            <td class="py-2 px-4 w-1/2">: {{ $schedule->office->name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 w-1/2"><strong>Shift</strong></td>
                            <td class="py-2 px-4 w-1/2">: {{ $schedule->shift->name }}
                                ({{ $schedule->shift->start_time }} -
                                {{ $schedule->shift->end_time }} WIB)</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4 w-1/2"><strong>Status</strong></td>
                            <td class="py-2 px-4 w-1/2">:
                                @if ($schedule->is_wfa)
                                    <span class="text-green-500">WFA</span>
                                @else
                                    WFO
                                @endif
                            </td>
                        </tr>
                    </table>
                    <div class="grid grid-cols-2 gap-6 mt-4 text-center">
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <h4 class="text-l font-bold mb-2">
                                Jam Masuk
                            </h4>
                            <p class="text-gray-700">{{ $attendance->start_time ?? '-' }} </p>
                        </div>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <h4 class="text-l font-bold mb-2">
                                Jam Pulang
                            </h4>
                            <p class="text-gray-700">{{ $attendance->end_time ?? '-' }} </p>
                        </div>
                    </div>
                    <form wire:submit="store" enctype="multipart/form-data">

                        <div class="grid-cols-2 gap-6 mt-4 w-full hidden md:grid">
                            @if (!$finish)
                                <button type="button" onclick="tagLocation()"
                                    class="w-full px-6 py-6 bg-blue-500 text-white rounded-md text-lg">Tag
                                    Location</button>
                                @if ($insideRadius)
                                    <button type="submit"
                                        class="w-full px-6 py-6 bg-green-500 text-white rounded-md text-lg">Submit
                                        Presensi</button>
                                @endif
                            @else
                                <div
                                    class="w-full p-4 bg-yellow-100 text-yellow-800 rounded-lg shadow-md flex items-center justify-center col-span-2">
                                    <p class="text-center font-semibold">✅ Anda Sudah Absen</p>
                                </div>
                            @endif
                        </div>

                    </form>
                </div>
                <div>
                    <h2 class="text-2xl font-bold mb-2">Presensi</h2>

                    <div wire:ignore id="map" class="mb-4 rounded-lg border border-gray-500"
                        style="height: 400px;"></div>
                    <form wire:submit="store" enctype="multipart/form-data">

                        <div class="sm:block md:hidden mt-4 grid grid-cols-2 gap-6">
                            @if (!$finish)
                                <button type="button" onclick="tagLocation()"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md">Tag Location</button>
                                @if ($insideRadius)
                                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md">Submit
                                        Presensi</button>
                                @endif
                            @else
                                <div
                                    class="w-full mt-4 p-4 bg-yellow-100 text-yellow-800 rounded-lg shadow-md flex items-center justify-center col-span-2">
                                    <p class="text-center font-semibold">✅ Anda Sudah Absen</p>
                                </div>
                            @endif
                        </div>

                    </form>
                </div>


            </div>
        </div>

    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;
        let lat;
        let lng;
        const office = [{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}];
        const radius = {{ $schedule->office->radius }};
        let component;

        document.addEventListener('livewire:initialized', function() {
            component = @this;

            map = L.map('map', {
                zoomControl: true,
                dragging: true
            }).setView([{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}], 16);

            const arcgis = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    minZoom: 1,
                    maxZoom: 18, // ArcGIS tetap mentok di 18
                    ext: 'png',
                }).addTo(map);

            const esri = L.tileLayer(
                'https://api.mapbox.com/styles/v1/mapbox/satellite-v9/tiles/{z}/{x}/{y}?access_token=pk.eyJ1Ijoicml2YWwyOCIsImEiOiJjbTgxOWVzeTAxMmV4Mmtva3BkamdyZG9vIn0.wAeygdcM3NfdPmCGGQnHnA', {
                    minZoom: 1,
                    maxZoom: 22, // ESRI punya beberapa zoom tambahan
                    ext: 'png',
                });

            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                minZoom: 1,
                maxZoom: 22,
            });

            // Layer control untuk ganti antara ArcGIS, ESRI, dan OSM
            L.control.layers({
                "ArcGIS Imagery": arcgis,
                "ESRI Satellite": esri,
                "OpenStreetMap": osm
            }).addTo(map);

            const circle = L.circle(office, {
                color: 'red',
                fillColor: '#f05',
                fillOpacity: 0.5,
                radius: radius
            }).addTo(map);



            let startLat = {{ $attendance->start_latitude ?? 'null' }};
            let startLng = {{ $startLng = $attendance->start_longitude ?? 'null' }};
            let endLat = {{ $attendance->end_latitude ?? 'null' }};
            let endLng = {{ $attendance->end_longitude ?? 'null' }};

            if (startLat !== null && startLng !== null) {
                marker = L.marker([startLat, startLng]).addTo(map);
                marker.bindPopup("Attendance Start Location").openPopup();
                map.setView([startLat, startLng], 18);
            }

            if (endLat !== null && endLng !== null) {
                marker = L.marker([endLat, endLng]).addTo(map);
                marker.bindPopup("Attendance End Location").openPopup();
                map.setView([endLat, endLng], 18);
            }



        });

        function tagLocation() {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                if (marker) {
                    map.removeLayer(marker);
                }

                if (isWithInRadius(lat, lng, office, radius)) {
                    component.set('insideRadius', true);
                    component.set('latitude', lat);
                    component.set('longitude', lng);
                }

                marker = L.marker([lat, lng]).addTo(map);
                marker.bindPopup("You are here").openPopup();
                map.setView([lat, lng], 18);
            }, function(error) {
                console.error("Error getting location: ", error);
            });
        };

        function isWithInRadius(lat, lng, office, radius) {

            const is_wfa = {{ $schedule->is_wfa }};
            if (is_wfa) {
                return true;
            } else {
                let distance = map.distance(
                    L.latLng(lat, lng),
                    office);
                return distance <= radius;
            }

        }
    </script>
</div>
