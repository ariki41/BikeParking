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
              const map = L.map('map').setView([{{ $nominatim[0]['lat'] }}, {{ $nominatim[0]['lon'] }}], 15); // 東京中心

              // OpenStreetMapタイルレイヤーを追加
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
              }).addTo(map);
          }
      </script>
  @endpush

  <x-app-layout>
      <div class="grid h-screen grid-cols-5">
          <div class="col-span-2 row-span-1 border border-gray-400">
              <form method="GET" action="{{ route('search') }}">
                  <div class="py-12">
                      <div class="flex items-center justify-center space-x-2">
                          <x-text-input class="w-3/5 max-w-3xl" id="keyword" name="keyword" type="text"
                              placeholder="駅名・地名を入力" :value="$keyword" />
                          <x-primary-button>検索</x-primary-button>
                      </div>
                  </div>
              </form>
          </div>

          <div class="col-span-3 row-span-6 border border-gray-400" id="map">
          </div>

          <div class="col-span-2 row-span-5 row-start-2 overflow-y-auto border border-gray-400">
              @for ($i = 0; $i < 10; $i++)
                  <div class="bg-gray-100 p-4">
                      <div class="mx-auto max-w-xl rounded-md bg-white p-4 shadow-lg">
                          <div class="flex flex-col gap-4 md:flex-row">
                              <div class="flex-shrink-0">
                                  <img class="h-auto w-full rounded-md md:w-64" src="image.png" alt="駐車場画像">
                              </div>

                              <div class="flex-1">
                                  <h2 class="mb-1 text-lg font-bold text-gray-700">駐車場1</h2>
                                  <p class="mb-4 text-sm text-gray-600">
                                      住所
                                  </p>

                                  <div class="flex items-center">
                                      <div class="flex items-center">
                                          <span class="text-2xl font-bold text-red-500">¥300</span>
                                          <span class="ml-2 text-gray-600">/</span>
                                          <span class="ml-2 text-gray-600">24h</span>
                                      </div>
                                      <div class="ml-auto">
                                          <span class="text-sm text-gray-600">0:00</span>
                                          <span class="text-sm text-gray-600">～</span>
                                          <span class="text-sm text-gray-600">24:00</span>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              @endfor
          </div>
      </div>
  </x-app-layout>
