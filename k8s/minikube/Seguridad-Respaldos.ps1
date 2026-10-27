# Funciones compartidas; ejecutar mediante Windows PowerShell 5.1.
Add-Type -AssemblyName System.Security
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Set-CarpetaPrivada([string]$Ruta) {
    if (-not (Test-Path -LiteralPath $Ruta)) { New-Item -ItemType Directory -Path $Ruta | Out-Null }
    $folder = Get-Item -LiteralPath $Ruta
    if ($folder.Attributes -band [IO.FileAttributes]::ReparsePoint) { throw 'No se permiten enlaces en la carpeta de respaldo.' }
    $sid = [Security.Principal.WindowsIdentity]::GetCurrent().User
    $acl = New-Object Security.AccessControl.DirectorySecurity
    # Conservar el propietario. Cambiarlo, incluso al mismo SID, puede exigir
    # WRITE_OWNER que no se hereda con el permiso Modify del directorio padre.
    $acl.SetAccessRuleProtection($true, $false)
    $inherit = [Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit'
    $rule = New-Object Security.AccessControl.FileSystemAccessRule($sid, 'FullControl', $inherit, 'None', 'Allow')
    $acl.AddAccessRule($rule)
    $systemSid = New-Object Security.Principal.SecurityIdentifier('S-1-5-18')
    $systemRule = New-Object Security.AccessControl.FileSystemAccessRule($systemSid, 'FullControl', $inherit, 'None', 'Allow')
    $acl.AddAccessRule($systemRule)
    # Set-Acl intenta persistir tambien la SACL en este entorno y exige
    # SeSecurityPrivilege. Esta API de .NET guarda solo las secciones modificadas.
    [IO.Directory]::SetAccessControl($Ruta, $acl)
}

function Get-HashBytes([byte[]]$Datos) {
    $sha = [Security.Cryptography.SHA256]::Create()
    try { return [BitConverter]::ToString($sha.ComputeHash($Datos)).Replace('-', '') }
    finally { $sha.Dispose() }
}

function Protect-ArchivoRespaldo([string]$Zip, [string]$Destino) {
    $plain = [IO.File]::ReadAllBytes($Zip)
    $cipher = [Security.Cryptography.ProtectedData]::Protect($plain, $null, [Security.Cryptography.DataProtectionScope]::CurrentUser)
    [IO.File]::WriteAllBytes($Destino, $cipher)
    $decoded = [Security.Cryptography.ProtectedData]::Unprotect([IO.File]::ReadAllBytes($Destino), $null, [Security.Cryptography.DataProtectionScope]::CurrentUser)
    $plainHash = Get-HashBytes $plain
    if ($plainHash -ne (Get-HashBytes $decoded)) { throw 'Fallo al verificar el descifrado del respaldo.' }
    return $plainHash
}
