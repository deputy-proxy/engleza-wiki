<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? config('app.name') }}</title>
<meta name="description" content="{{ $description ?? 'Free English resources, tests and lessons for learners and teachers.' }}">
<meta property="og:site_name" content="engleza.wiki">
<meta property="og:title" content="{{ $title ?? config('app.name') }}">
<meta property="og:description" content="{{ $description ?? 'Free English resources and tests.' }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css','resources/js/app.js'])
@livewireStyles
</head>
<body class="min-h-screen bg-white text-navy-900" data-testid="app-body">
<header class="border-b border-navy-100/70 bg-white/95 backdrop-blur sticky top-0 z-40">
  <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between gap-8">
    <a href="{{ route('home') }}" class="flex items-center gap-2" data-testid="brand-link">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl">
        <?xml version="1.0" encoding="UTF-8" standalone="no"?>
          <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
          <svg width="100%" height="100%" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:1.5;">
              <g>
                  <g transform="matrix(1.451504,0.318623,0,1.503165,-1131.637575,-548.086615)">
                      <path d="M956,478L792,478L792,234L956,234L1120,162L1120,406L956,478Z" style="fill:none;stroke:rgb(1,33,105);stroke-width:2.43px;"/>
                  </g>
                  <g transform="matrix(0.841124,0,0,0.841124,40.681053,3.275859)">
                      <g opacity="0.7">
                          <g transform="matrix(1.44532,0.314082,0,1.663378,-92.322197,-159.028796)">
                              <clipPath id="_clip1">
                                  <rect x="93.986" y="96" width="147.014" height="204.532"/>
                              </clipPath>
                              <g clip-path="url(#_clip1)">
                                  <g transform="matrix(5.326346,0,0,5.326346,93.985714,96)">
                                      <path d="M2.87,38.4L52.33,38.4C53.92,38.31 55.2,36.98 55.2,35.37L55.2,3.03C55.2,1.37 53.85,0.01 52.19,0L3.01,0C1.35,0.01 0,1.37 0,3.03L0,35.36C0,36.98 1.28,38.31 2.87,38.4Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M23.74,23.03L23.74,38.4L31.42,38.4L31.42,23.03L55.2,23.03L55.2,15.35L31.42,15.35L31.42,0L23.74,0L23.74,15.35L0,15.35L0,23.03L23.74,23.03Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M33.98,12.43L33.98,0L52.21,0C53.47,0.02 54.55,0.81 54.99,1.92L33.98,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M33.98,25.97L33.98,38.4L52.33,38.4C53.54,38.33 54.56,37.55 54.99,36.48L33.98,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M21.18,25.97L21.18,38.4L2.87,38.4C1.66,38.33 0.63,37.55 0.21,36.46L21.18,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M21.18,12.43L21.18,0L2.99,0C1.73,0.02 0.64,0.82 0.21,1.94L21.18,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M0,12.8L7.65,12.8L0,8.97L0,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M55.2,12.8L47.51,12.8L55.2,8.95L55.2,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M55.2,25.6L47.51,25.6L55.2,29.45L55.2,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M0,25.6L7.65,25.6L0,29.43L0,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M55.2,3.25L36.15,12.8L40.41,12.8L55.2,5.4L55.2,3.25Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M19.05,25.6L14.75,25.6L0,32.98L0,35.13L19.05,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M10.52,12.81L14.78,12.81L0,5.41L0,7.55L10.52,12.81Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M44.63,25.59L40.37,25.59L55.2,33.02L55.2,30.88L44.63,25.59Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                  </g>
                              </g>
                          </g>
                          <g transform="matrix(-1.44532,0.314082,0,1.663378,604.30155,-159.028796)">
                              <clipPath id="_clip2">
                                  <rect x="93.986" y="96" width="147.014" height="204.532"/>
                              </clipPath>
                              <g clip-path="url(#_clip2)">
                                  <g transform="matrix(5.326346,0,0,5.326346,93.985714,96)">
                                      <path d="M2.87,38.4L52.33,38.4C53.92,38.31 55.2,36.98 55.2,35.37L55.2,3.03C55.2,1.37 53.85,0.01 52.19,0L3.01,0C1.35,0.01 0,1.37 0,3.03L0,35.36C0,36.98 1.28,38.31 2.87,38.4Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M23.74,23.03L23.74,38.4L31.42,38.4L31.42,23.03L55.2,23.03L55.2,15.35L31.42,15.35L31.42,0L23.74,0L23.74,15.35L0,15.35L0,23.03L23.74,23.03Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M33.98,12.43L33.98,0L52.21,0C53.47,0.02 54.55,0.81 54.99,1.92L33.98,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M33.98,25.97L33.98,38.4L52.33,38.4C53.54,38.33 54.56,37.55 54.99,36.48L33.98,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M21.18,25.97L21.18,38.4L2.87,38.4C1.66,38.33 0.63,37.55 0.21,36.46L21.18,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M21.18,12.43L21.18,0L2.99,0C1.73,0.02 0.64,0.82 0.21,1.94L21.18,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M0,12.8L7.65,12.8L0,8.97L0,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M55.2,12.8L47.51,12.8L55.2,8.95L55.2,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M55.2,25.6L47.51,25.6L55.2,29.45L55.2,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M0,25.6L7.65,25.6L0,29.43L0,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M55.2,3.25L36.15,12.8L40.41,12.8L55.2,5.4L55.2,3.25Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M19.05,25.6L14.75,25.6L0,32.98L0,35.13L19.05,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M10.52,12.81L14.78,12.81L0,5.41L0,7.55L10.52,12.81Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M44.63,25.59L40.37,25.59L55.2,33.02L55.2,30.88L44.63,25.59Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                  </g>
                              </g>
                          </g>
                      </g>
                  </g>
                  <g transform="matrix(0.905825,0,0,0.905825,24.118057,14.498169)">
                      <g opacity="0.7">
                          <g transform="matrix(1.44532,0.314082,0,1.663378,-92.322197,-159.028796)">
                              <clipPath id="_clip3">
                                  <rect x="93.986" y="96" width="147.014" height="204.532"/>
                              </clipPath>
                              <g clip-path="url(#_clip3)">
                                  <g transform="matrix(5.326346,0,0,5.326346,93.985714,96)">
                                      <path d="M2.87,38.4L52.33,38.4C53.92,38.31 55.2,36.98 55.2,35.37L55.2,3.03C55.2,1.37 53.85,0.01 52.19,0L3.01,0C1.35,0.01 0,1.37 0,3.03L0,35.36C0,36.98 1.28,38.31 2.87,38.4Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M23.74,23.03L23.74,38.4L31.42,38.4L31.42,23.03L55.2,23.03L55.2,15.35L31.42,15.35L31.42,0L23.74,0L23.74,15.35L0,15.35L0,23.03L23.74,23.03Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M33.98,12.43L33.98,0L52.21,0C53.47,0.02 54.55,0.81 54.99,1.92L33.98,12.43Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M33.98,25.97L33.98,38.4L52.33,38.4C53.54,38.33 54.56,37.55 54.99,36.48L33.98,25.97Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M21.18,25.97L21.18,38.4L2.87,38.4C1.66,38.33 0.63,37.55 0.21,36.46L21.18,25.97Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M21.18,12.43L21.18,0L2.99,0C1.73,0.02 0.64,0.82 0.21,1.94L21.18,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M0,12.8L7.65,12.8L0,8.97L0,12.8Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M55.2,12.8L47.51,12.8L55.2,8.95L55.2,12.8Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M55.2,25.6L47.51,25.6L55.2,29.45L55.2,25.6Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M0,25.6L7.65,25.6L0,29.43L0,25.6Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M55.2,3.25L36.15,12.8L40.41,12.8L55.2,5.4L55.2,3.25Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M19.05,25.6L14.75,25.6L0,32.98L0,35.13L19.05,25.6Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M10.52,12.81L14.78,12.81L0,5.41L0,7.55L10.52,12.81Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M44.63,25.59L40.37,25.59L55.2,33.02L55.2,30.88L44.63,25.59Z" style="fill:white;fill-rule:nonzero;"/>
                                  </g>
                              </g>
                          </g>
                          <g transform="matrix(-1.44532,0.314082,0,1.663378,604.30155,-159.028796)">
                              <clipPath id="_clip4">
                                  <rect x="93.986" y="96" width="147.014" height="204.532"/>
                              </clipPath>
                              <g clip-path="url(#_clip4)">
                                  <g transform="matrix(5.326346,0,0,5.326346,93.985714,96)">
                                      <path d="M2.87,38.4L52.33,38.4C53.92,38.31 55.2,36.98 55.2,35.37L55.2,3.03C55.2,1.37 53.85,0.01 52.19,0L3.01,0C1.35,0.01 0,1.37 0,3.03L0,35.36C0,36.98 1.28,38.31 2.87,38.4Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M23.74,23.03L23.74,38.4L31.42,38.4L31.42,23.03L55.2,23.03L55.2,15.35L31.42,15.35L31.42,0L23.74,0L23.74,15.35L0,15.35L0,23.03L23.74,23.03Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M33.98,12.43L33.98,0L52.21,0C53.47,0.02 54.55,0.81 54.99,1.92L33.98,12.43Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M33.98,25.97L33.98,38.4L52.33,38.4C53.54,38.33 54.56,37.55 54.99,36.48L33.98,25.97Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M21.18,25.97L21.18,38.4L2.87,38.4C1.66,38.33 0.63,37.55 0.21,36.46L21.18,25.97Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M21.18,12.43L21.18,0L2.99,0C1.73,0.02 0.64,0.82 0.21,1.94L21.18,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                                      <path d="M0,12.8L7.65,12.8L0,8.97L0,12.8Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M55.2,12.8L47.51,12.8L55.2,8.95L55.2,12.8Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M55.2,25.6L47.51,25.6L55.2,29.45L55.2,25.6Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M0,25.6L7.65,25.6L0,29.43L0,25.6Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M55.2,3.25L36.15,12.8L40.41,12.8L55.2,5.4L55.2,3.25Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M19.05,25.6L14.75,25.6L0,32.98L0,35.13L19.05,25.6Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M10.52,12.81L14.78,12.81L0,5.41L0,7.55L10.52,12.81Z" style="fill:white;fill-rule:nonzero;"/>
                                      <path d="M44.63,25.59L40.37,25.59L55.2,33.02L55.2,30.88L44.63,25.59Z" style="fill:white;fill-rule:nonzero;"/>
                                  </g>
                              </g>
                          </g>
                      </g>
                  </g>
                  <g transform="matrix(-1.44532,0.314082,0,1.663378,604.322197,-137.708308)">
                      <clipPath id="_clip5">
                          <rect x="93.986" y="96" width="147.014" height="204.532"/>
                      </clipPath>
                      <g clip-path="url(#_clip5)">
                          <g transform="matrix(5.326346,0,0,5.326346,93.985714,96)">
                              <path d="M2.87,38.4L52.33,38.4C53.92,38.31 55.2,36.98 55.2,35.37L55.2,3.03C55.2,1.37 53.85,0.01 52.19,0L3.01,0C1.35,0.01 0,1.37 0,3.03L0,35.36C0,36.98 1.28,38.31 2.87,38.4Z" style="fill:rgb(254,254,254);fill-rule:nonzero;"/>
                              <path d="M23.74,23.03L23.74,38.4L31.42,38.4L31.42,23.03L55.2,23.03L55.2,15.35L31.42,15.35L31.42,0L23.74,0L23.74,15.35L0,15.35L0,23.03L23.74,23.03Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M33.98,12.43L33.98,0L52.21,0C53.47,0.02 54.55,0.81 54.99,1.92L33.98,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M33.98,25.97L33.98,38.4L52.33,38.4C53.54,38.33 54.56,37.55 54.99,36.48L33.98,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M21.18,25.97L21.18,38.4L2.87,38.4C1.66,38.33 0.63,37.55 0.21,36.46L21.18,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M21.18,12.43L21.18,0L2.99,0C1.73,0.02 0.64,0.82 0.21,1.94L21.18,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M0,12.8L7.65,12.8L0,8.97L0,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M55.2,12.8L47.51,12.8L55.2,8.95L55.2,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M55.2,25.6L47.51,25.6L55.2,29.45L55.2,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M0,25.6L7.65,25.6L0,29.43L0,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M55.2,3.25L36.15,12.8L40.41,12.8L55.2,5.4L55.2,3.25Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M19.05,25.6L14.75,25.6L0,32.98L0,35.13L19.05,25.6Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M10.52,12.81L14.78,12.81L0,5.41L0,7.55L10.52,12.81Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M44.63,25.59L40.37,25.59L55.2,33.02L55.2,30.88L44.63,25.59Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                          </g>
                      </g>
                  </g>
                  <g transform="matrix(1.44532,0.314082,0,1.663378,-92.322197,-137.708308)">
                      <clipPath id="_clip6">
                          <rect x="93.986" y="96" width="147.014" height="204.532"/>
                      </clipPath>
                      <g clip-path="url(#_clip6)">
                          <g transform="matrix(5.326346,0,0,5.326346,93.985714,96)">
                              <path d="M2.87,38.4L52.33,38.4C53.92,38.31 55.2,36.98 55.2,35.37L55.2,3.03C55.2,1.37 53.85,0.01 52.19,0L3.01,0C1.35,0.01 0,1.37 0,3.03L0,35.36C0,36.98 1.28,38.31 2.87,38.4Z" style="fill:rgb(254,254,254);fill-rule:nonzero;"/>
                              <path d="M23.74,23.03L23.74,38.4L31.42,38.4L31.42,23.03L55.2,23.03L55.2,15.35L31.42,15.35L31.42,0L23.74,0L23.74,15.35L0,15.35L0,23.03L23.74,23.03Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M33.98,12.43L33.98,0L52.21,0C53.47,0.02 54.55,0.81 54.99,1.92L33.98,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M33.98,25.97L33.98,38.4L52.33,38.4C53.54,38.33 54.56,37.55 54.99,36.48L33.98,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M21.18,25.97L21.18,38.4L2.87,38.4C1.66,38.33 0.63,37.55 0.21,36.46L21.18,25.97Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M21.18,12.43L21.18,0L2.99,0C1.73,0.02 0.64,0.82 0.21,1.94L21.18,12.43Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M0,12.8L7.65,12.8L0,8.97L0,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M55.2,12.8L47.51,12.8L55.2,8.95L55.2,12.8Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M55.2,25.6L47.51,25.6L55.2,29.45L55.2,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M0,25.6L7.65,25.6L0,29.43L0,25.6Z" style="fill:rgb(1,33,105);fill-rule:nonzero;"/>
                              <path d="M55.2,3.25L36.15,12.8L40.41,12.8L55.2,5.4L55.2,3.25Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M19.05,25.6L14.75,25.6L0,32.98L0,35.13L19.05,25.6Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M10.52,12.81L14.78,12.81L0,5.41L0,7.55L10.52,12.81Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                              <path d="M44.63,25.59L40.37,25.59L55.2,33.02L55.2,30.88L44.63,25.59Z" style="fill:rgb(200,16,46);fill-rule:nonzero;"/>
                          </g>
                      </g>
                  </g>
              </g>
          </svg>

      </span>
      <span class="text-xl font-bold tracking-tight text-navy-900">engleza<span class="text-brand-500">.wiki</span></span>
    </a>
    <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-navy-700">
      <a href="{{ route('home') }}" class="hover:text-brand-600" data-testid="nav-home">Home</a>
      <a href="{{ route('resources.index') }}" class="hover:text-brand-600" data-testid="nav-resources">Resources</a>
      <a href="{{ route('grammar') }}" class="hover:text-brand-600" data-testid="nav-grammar">Grammar</a>
      <a href="{{ route('vocabulary') }}" class="hover:text-brand-600" data-testid="nav-vocabulary">Vocabulary</a>
      <a href="{{ route('for-teachers') }}" class="hover:text-brand-600" data-testid="nav-for-teachers">For Teachers</a>
      <a href="{{ route('about') }}" class="hover:text-brand-600" data-testid="nav-about">About</a>
    </nav>
    <div class="flex items-center gap-3">
      <a href="{{ route('search') }}" class="p-2 text-navy-700 hover:text-brand-600" data-testid="search-icon" aria-label="Search">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </a>
      @auth
        <a href="{{ route('dashboard') }}" class="btn-ghost" data-testid="nav-dashboard">Dashboard</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-outline" data-testid="nav-logout">Log out</button></form>
      @else
        <a href="{{ route('login') }}" class="btn-ghost" data-testid="nav-login">Log in</a>
        <a href="{{ route('tests.index') }}" class="btn-primary" data-testid="nav-cta-test">Test your English</a>
      @endauth
    </div>
  </div>
</header>

<main data-testid="main-content">
  {{ $slot }}
</main>

<footer class="mt-20 border-t border-navy-100/70 bg-navy-50/50">
  <div class="mx-auto max-w-7xl px-6 py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h6a3 3 0 013 3v11a2 2 0 00-2-2H4V5zm16 0h-6a3 3 0 00-3 3v11a2 2 0 012-2h7V5z"/></svg>
      </span>
      <span class="font-semibold text-navy-900">engleza.wiki</span>
    </div>
    <p class="text-sm text-navy-600">Free resources for learners, teachers and curious minds.</p>
    <div class="text-sm text-navy-600">© {{ date('Y') }} engleza.wiki</div>
  </div>
</footer>

@livewireScripts
</body>
</html>
