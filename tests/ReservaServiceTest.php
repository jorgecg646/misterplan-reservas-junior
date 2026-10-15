<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\ReservaService;
use App\Repositories\ReservaRepository;
use App\Repositories\EventoRepository;
use App\Exceptions\ValidationException;
use PDO;

/**
 * Pruebas unitarias para la lógica de negocio y validación de reservas.
 * Verifican que el servicio aplique correctamente las reglas del negocio.
 */
class ReservaServiceTest extends TestCase
{
    private PDO $pdoMock;
    private $reservaRepoMock;
    private $eventoRepoMock;
    private ReservaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Creamos mocks para aislar la lógica del servicio sin necesidad de BD externa
        $this->pdoMock = $this->createMock(PDO::class);
        $this->reservaRepoMock = $this->createMock(ReservaRepository::class);
        $this->eventoRepoMock = $this->createMock(EventoRepository::class);

        $this->service = new ReservaService(
            $this->pdoMock,
            $this->reservaRepoMock,
            $this->eventoRepoMock
        );
    }

    /**
     * Test 1: Comprobar que rechaza si la fecha de salida es anterior a la de entrada
     */
    public function testRechazaFechaSalidaAnteriorAEntrada(): void
    {
        $datosInvalidos = [
            'nombre_huesped'     => 'Carlos Gómez',
            'email_huesped'      => 'carlos@email.com',
            'nombre_alojamiento' => 'Apartamento Playa',
            'fecha_entrada'      => '2026-11-15',
            'fecha_salida'       => '2026-11-10', // Salida antes que entrada
            'importe'            => 300.00,
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->service->crearReserva($datosInvalidos);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fecha_salida', $e->getErrores());
            throw $e;
        }
    }

    /**
     * Test 2: Comprobar que rechaza un correo electrónico con formato inválido
     */
    public function testRechazaEmailConFormatoInvalido(): void
    {
        $datosInvalidos = [
            'nombre_huesped'     => 'Carlos Gómez',
            'email_huesped'      => 'correo-sin-arroba-ni-dominio',
            'nombre_alojamiento' => 'Apartamento Playa',
            'fecha_entrada'      => '2026-11-10',
            'fecha_salida'       => '2026-11-15',
            'importe'            => 300.00,
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->service->crearReserva($datosInvalidos);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email_huesped', $e->getErrores());
            throw $e;
        }
    }

    /**
     * Test 3: Comprobar que rechaza un importe igual o menor a cero
     */
    public function testRechazaImporteCeroONegativo(): void
    {
        $datosInvalidos = [
            'nombre_huesped'     => 'Carlos Gómez',
            'email_huesped'      => 'carlos@email.com',
            'nombre_alojamiento' => 'Apartamento Playa',
            'fecha_entrada'      => '2026-11-10',
            'fecha_salida'       => '2026-11-15',
            'importe'            => -50.00, // Importe negativo
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->service->crearReserva($datosInvalidos);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('importe', $e->getErrores());
            throw $e;
        }
    }

    /**
     * Test 4: Comprobar que rechaza cambios a estados no permitidos
     */
    public function testRechazaEstadoNoPermitido(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->cambiarEstado(1, 'estado_inexistente');
    }

    /**
     * Test 5: Comprobar que una reserva válida se crea correctamente y genera su evento
     */
    public function testCrearReservaValidaRegistraReservaYEvento(): void
    {
        $datosValidos = [
            'nombre_huesped'     => 'Laura Martínez',
            'email_huesped'      => 'laura@email.com',
            'nombre_alojamiento' => 'Hotel Rural El Mirador',
            'fecha_entrada'      => '2026-11-01',
            'fecha_salida'       => '2026-11-05',
            'importe'            => 380.00,
            'notas'              => 'Cuna solicitada',
        ];

        // Se espera que inicie y confirme la transacción
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        // Se espera que llame a crear en el repositorio de reservas y devuelva ID 99
        $this->reservaRepoMock->expects($this->once())
            ->method('crear')
            ->willReturn(99);

        // Se espera que registre el evento de creación en el repositorio de eventos
        $this->eventoRepoMock->expects($this->once())
            ->method('crear')
            ->with(99, 'creacion', $this->anything())
            ->willReturn(true);

        // Se espera que recupere el detalle final
        $this->reservaRepoMock->expects($this->once())
            ->method('obtenerPorId')
            ->with(99)
            ->willReturn(array_merge(['id' => 99, 'estado' => 'pendiente'], $datosValidos));

        $this->eventoRepoMock->expects($this->once())
            ->method('obtenerPorReservaId')
            ->with(99)
            ->willReturn([
                ['id' => 1, 'reserva_id' => 99, 'tipo' => 'creacion', 'descripcion' => 'Creada', 'fecha_creacion' => '2026-10-15']
            ]);

        $resultado = $this->service->crearReserva($datosValidos);

        $this->assertNotNull($resultado);
        $this->assertEquals(99, $resultado['reserva']['id']);
        $this->assertCount(1, $resultado['eventos']);
    }
}
