  @push('link')
      <!-- LeafletのCSS -->
      <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  @endpush

  @push('script')
      <!-- LeafletのJavaScript -->
      <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

      <script>
          // 地図を初期化
          window.onload = function() {
              const map = L.map('map').setView([35.6895, 139.6917], 13); // 東京中心

              // OpenStreetMapタイルレイヤーを追加
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
              }).addTo(map);
          }
      </script>
  @endpush

  <x-app-layout>
      <div class="grid h-full grid-cols-4 grid-rows-6">

          <div class="border border-gray-400">
              <form method="GET" action="{{ route('search') }}">
                  <div class="py-12">
                      <div class="flex items-center justify-center space-x-2">
                          <x-text-input class="w-3/5 max-w-3xl" id="keyword" name="keyword" type="text"
                              :value="old('keyword')" />
                          <x-primary-button>検索</x-primary-button>
                      </div>
                  </div>
              </form>
          </div>

          <div class="col-span-3 row-span-6 border border-gray-400" id="map">
          </div>

          <div class="row-span-6 row-start-2 border border-gray-400">
              検索条件
          </div>
      </div>
  </x-app-layout>
