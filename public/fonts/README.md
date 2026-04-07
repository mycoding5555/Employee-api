Place the Khmer font files you want to use here (not committed if you prefer).

Required filenames (used by CSS):
- KhmerOSSiemreap-Regular.ttf
- KhmerOSMuolLight.ttf
- (optional) KhmerOSSiemreap-Regular.woff2
- (optional) KhmerOSSiemreap-Regular.woff
- (optional) KhmerOSMuolLight.woff2
- (optional) KhmerOSMuolLight.woff

Options to install fonts:
1) Manual: download the .ttf files from a trusted source and copy them into `public/fonts/` with the exact names above.
2) Automated: use the provided script `scripts/install-khmer-fonts.sh` with two arguments (Siemreap-URL and Muol-URL):

```
./scripts/install-khmer-fonts.sh https://example.com/KhmerOSSiemreap-Regular.ttf https://example.com/KhmerOSMuolLight.ttf
```

The script will save TTF files to `public/fonts/` and will generate `.woff2` (if `woff2_compress` is installed) and `.woff` (if `ttf2woff` is installed).

After placing fonts, rebuild assets:

```
npm run dev
# or
npm run build
```

If you want me to attempt fetching real download URLs and run the script, tell me to proceed and provide permission to download from the web.