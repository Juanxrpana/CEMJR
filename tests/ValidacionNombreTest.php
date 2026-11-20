<?php

use App\config\ValidacionNombre;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidacionNombreTest extends TestCase
{
    #[DataProvider('nombresValidos')]
    public function testAceptaNombresValidos(string $valor): void
    {
        $this->assertSame($valor, ValidacionNombre::validar($valor, 55, 'nombre'));
    }

    public static function nombresValidos(): array
    {
        return [
            'articulo con la' => ['De la Cruz'],
            'articulo Del' => ['Del Valle'],
            'articulo Los' => ['De Los Santos'],
            'dos articulos' => ['María De Los Ángeles'],
            'sufijo Jr al final' => ['Juan Pérez Jr'],
            'Jr en medio' => ['Juan Jr Pérez'],
            'apellido compuesto' => ['De Los Santos'],
        ];
    }

    #[DataProvider('nombresInvalidos')]
    public function testRechazaNombresInvalidos(string $valor): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValidacionNombre::validar($valor, 55, 'nombre');
    }

    public static function nombresInvalidos(): array
    {
        return [
            'todo en minuscula' => ['de la cruz'],
            'solo particula' => ['De'],
            'solo Jr' => ['Jr'],
            'Jr con punto' => ['Jr.'],
            'guion' => ['Jean-Pierre'],
            'doble espacio' => ['Juan  Pérez'],
            'html' => ['<b>Juan</b>'],
            'numero' => ['Juan123'],
        ];
    }
}
