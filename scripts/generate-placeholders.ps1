# Generates branded placeholder JPGs in public/placeholders (Windows / System.Drawing).
# Prefer: php artisan placeholders:generate

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$outDir = Join-Path $PSScriptRoot '..\public\placeholders'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$bg = [System.Drawing.Color]::FromArgb(230, 230, 230)
$fg = [System.Drawing.Color]::FromArgb(143, 143, 143)

$fontCandidates = @(
    'Segoe UI Light',
    'Segoe UI',
    'Calibri Light',
    'Calibri',
    'Arial'
)

function Get-Font([int]$size) {
    foreach ($name in $fontCandidates) {
        try {
            return New-Object System.Drawing.Font($name, $size, [System.Drawing.FontStyle]::Regular)
        } catch {}
    }
    return New-Object System.Drawing.Font([System.Drawing.FontFamily]::GenericSansSerif, $size)
}

function New-PlaceholderImage([int]$width, [int]$height, [string[]]$lines, [int]$fontSize, [string]$fileName) {
    $bmp = New-Object System.Drawing.Bitmap $width, $height
    $graphics = [System.Drawing.Graphics]::FromImage($bmp)
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    $graphics.Clear($bg)

    $font = Get-Font $fontSize
    $brush = New-Object System.Drawing.SolidBrush($fg)
    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Center
    $format.LineAlignment = [System.Drawing.StringAlignment]::Center

    $text = ($lines -join "`n")
    $rect = [System.Drawing.RectangleF]::new(0, 0, $width, $height)
    $graphics.DrawString($text, $font, $brush, $rect, $format)

    $path = Join-Path $outDir $fileName
    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Jpeg)

    $graphics.Dispose()
    $bmp.Dispose()
    $font.Dispose()
    $brush.Dispose()

    Write-Host "Wrote $path"
}

New-PlaceholderImage 500 500 @('ADS OF IRAQ') 28 'placeholder-square.jpg'
New-PlaceholderImage 1200 675 @('ADS OF IRAQ') 42 'placeholder-landscape.jpg'
New-PlaceholderImage 800 1200 @('ADS OF', 'IRAQ') 36 'placeholder-portrait.jpg'
