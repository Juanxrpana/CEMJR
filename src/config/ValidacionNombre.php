<?php

namespace App\config;

/**
 * Validación de nombres y apellidos de personas.
 *
 * Acepta palabras con inicial mayúscula, las partículas "De", "Del", "La" y
 * "Los" (con variantes de capitalización) y el sufijo/generacional "Jr".
 * No acepta guiones, números, HTML ni palabras en minúscula.
 */
final class ValidacionNombre
{
    public const PATRON = '/^(?=.*[A-ZÁÉÍÓÚÑ][a-záéíóúñ]{2,})(?:(?:[A-ZÁÉÍÓÚÑ][a-záéíóúñ]{2,})|(?:De|de|Del|del|La|la|Los|los|Jr))(?: (?:(?:[A-ZÁÉÍÓÚÑ][a-záéíóúñ]{2,})|(?:De|de|Del|del|La|la|Los|los|Jr)))*$/u';

    /**
     * Valida un nombre o apellido y devuelve el texto limpio (sin espacios en
     * los extremos). Lanza InvalidArgumentException si no cumple la regla.
     */
    public static function validar($valor, int $max, string $campo): string
    {
        if (!is_scalar($valor)) {
            throw new \InvalidArgumentException("El $campo no es válido.");
        }

        $texto = trim((string) $valor);
        $longitud = mb_strlen($texto, 'UTF-8');

        if ($longitud < 3 || $longitud > $max) {
            throw new \InvalidArgumentException("El $campo debe tener entre 3 y $max caracteres.");
        }

        if (!preg_match(self::PATRON, $texto)) {
            throw new \InvalidArgumentException(
                "El $campo debe iniciar con mayúscula, usar solo letras y espacios, y permitir las partículas De, Del, La, Los y Jr."
            );
        }

        return $texto;
    }
}
