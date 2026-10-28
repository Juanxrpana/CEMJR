<#
    Quita el prefijo de ruta "/Sistema-del--CEM--JEHOVA-RAFA" de todo el runtime,
    para que la app funcione servida en la raiz (/).

    Uso (desde la raiz del proyecto o desde cualquier lado):
        powershell -ExecutionPolicy Bypass -File scripts\quitar-prefijo-rutas.ps1

    Es idempotente: si no hay prefijos, no hace nada.
    No toca codigo de terceros (vendor, library, Plugins) ni tests/selenium.
    Pensado para correr DESPUES de cada "git pull"/"git merge".
#>

$ErrorActionPreference = 'Stop'

$root    = Split-Path -Parent $PSScriptRoot
$prefijo = '/Sistema-del--CEM--JEHOVA-RAFA'
# ISO-8859-1 mapea 1 byte = 1 caracter, asi se preserva el encoding original
# (UTF-8, ANSI, etc.) porque el prefijo es ASCII puro.
$enc     = [System.Text.Encoding]::GetEncoding(28591)
$excluir = '\\(library|Plugins|tests|selenium|vendor|node_modules|\.git)\\'

$files = @()
$files += Get-ChildItem -LiteralPath (Join-Path $root 'src') -Recurse -File |
    Where-Object { $_.Extension -in '.js', '.php' -and $_.FullName -notmatch $excluir }

foreach ($rel in @('index.php', '.htaccess', 'webSocket.php')) {
    $p = Join-Path $root $rel
    if (Test-Path -LiteralPath $p) { $files += Get-Item -LiteralPath $p }
}

$changed = @()
foreach ($f in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
    $text  = $enc.GetString($bytes)
    if ($text.Contains($prefijo)) {
        [System.IO.File]::WriteAllBytes($f.FullName, $enc.GetBytes($text.Replace($prefijo, '')))
        $changed += $f.FullName
    }
}

if ($changed.Count -eq 0) {
    Write-Host 'OK: no habia prefijo que quitar.'
} else {
    Write-Host ("Se quito el prefijo en {0} archivos:" -f $changed.Count)
    $changed | Sort-Object | ForEach-Object {
        Write-Host (" - {0}" -f $_.Substring($root.Length).TrimStart('\'))
    }
}
