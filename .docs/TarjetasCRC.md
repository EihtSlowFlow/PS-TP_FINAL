### 1. Módulo de Usuarios y Seguridad

#### Tarjeta CRC: `Usuario`

| **Usuario**                                                                                             |                   |
| ------------------------------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                                                   | **Colaboradores** |
| • Representar la entidad genérica de persona en el sistema (Nombre, DNI, Email, Teléfono, Rol, Estado). | • `AuthService`   |
| • Autenticarse mediante verificación de clave BCRYPT.                                                   |                   |
| • Actualizar sus datos de perfil y contraseña.                                                          |                   |
| • Aplicar baja lógica (_Soft Delete_).                                                                  |                   |

#### Tarjeta CRC: `AuthService`

| **AuthService**                                                                           |                   |
| ----------------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                                     | **Colaboradores** |
| • Gestionar el inicio, mantenimiento y cierre de sesión.                                  | • `Usuario`       |
| • Interceptar peticiones para validar autenticación y roles (`AuthFilter`, `RoleFilter`). |                   |
| • Gestionar el flujo de recuperación y restablecimiento de contraseña mediante tokens.    |                   |

---

### 2. Módulo de Propiedades y Contratos

#### Tarjeta CRC: `Inmueble`

| **Inmueble**                                                                              |                           |
| ----------------------------------------------------------------------------------------- | ------------------------- |
| **Responsabilidades**                                                                     | **Colaboradores**         |
| • Guardar características del inmueble (Dirección, Tipo, Ambientes, Precio base, Estado). | • `Usuario` (Propietario) |
| • Validar disponibilidad para nuevos contratos.                                           | • `Contrato`              |
| • Cambiar de estado (`Disponible`, `Alquilado`, `En Mantenimiento`).                      |                           |
| • Restringir su eliminación si posee contrato vigente.                                    |                           |

#### Tarjeta CRC: `Contrato`

| **Contrato**                                                                                                  |                                       |
| ------------------------------------------------------------------------------------------------------------- | ------------------------------------- |
| **Responsabilidades**                                                                                         | **Colaboradores**                     |
| • Vincular un inmueble con un inquilino y fijar condiciones de locación (Monto, Comisión, Mora, Vencimiento). | • `Inmueble`                          |
| • Controlar el estado del acuerdo (`Vigente`, `Finalizado`, `Rescindido`).                                    | • `Usuario` (Inquilino y Propietario) |
| • Desencadenar el cambio de estado del inmueble asociado.                                                     |                                       |

---

### 3. Módulo Financiero: Cuotas y Moras

#### Tarjeta CRC: `Cuota`

| **Cuota**                                                                                                                    |                    |
| ---------------------------------------------------------------------------------------------------------------------------- | ------------------ |
| **Responsabilidades**                                                                                                        | **Colaboradores**  |
| • Almacenar período, fechas de vencimiento, monto base, recargos y estado (`Pendiente`, `Vencida`, `En Revisión`, `Pagada`). | • `Contrato`       |
| • Calcular automáticamente recargos por mora en función de los días transcurridos.                                           | • `CalculadorMora` |

#### Tarjeta CRC: `CalculadorMora`

| **CalculadorMora**                                                                      |                   |
| --------------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                                   | **Colaboradores** |
| • Calcular días de atraso (`DATEDIFF`) entre la fecha actual y la fecha de vencimiento. | • `Cuota`         |
| • Aplicar el porcentaje diario pactado en el contrato sobre el monto base.              | • `Contrato`      |

#### Tarjeta CRC: `GestorEmisionCuotas`

| **GestorEmisionCuotas**                                            |                   |
| ------------------------------------------------------------------ | ----------------- |
| **Responsabilidades**                                              | **Colaboradores** |
| • Consultar los contratos vigentes para un período determinado.    | • `Contrato`      |
| • Generar masivamente los registros de cuotas evitando duplicados. | • `Cuota`         |

---

### 4. Módulo de Pagos y Validaciones

#### Tarjeta CRC: `Pago`

| **Pago**                                                                                       |                   |
| ---------------------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                                          | **Colaboradores** |
| • Registrar los datos del pago (Monto transferido, Fecha, Medio de pago, Referencia bancaria). | • `Cuota`         |
| • Almacenar el estado del pago (`Pendiente de Validación`, `Aprobado`, `Rechazado`).           | • `Comprobante`   |
| • Mantener el motivo de rechazo en caso de no ser aprobado.                                    |                   |

#### Tarjeta CRC: `Comprobante`

| **Comprobante**                                                                          |                   |
| ---------------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                                    | **Colaboradores** |
| • Validar extensión (PDF, JPG, PNG) y peso (máx. 5MB) del archivo digital.               | • `Pago`          |
| • Almacenar el archivo físico de forma segura en `writable/uploads/` con hash aleatorio. |                   |
| • Servir la descarga del archivo mediante controladores protegidos.                      |                   |

---

### 5. Módulo de Liquidaciones

#### Tarjeta CRC: `Liquidacion`

| **Liquidacion**                                                                                     |                           |
| --------------------------------------------------------------------------------------------------- | ------------------------- |
| **Responsabilidades**                                                                               | **Colaboradores**         |
| • Calcular el monto bruto, la comisión de la inmobiliaria y el neto a transferir.                   | • `Cuota`                 |
| • Gestionar el estado de la rendición al propietario (`Pendiente de Transferencia`, `Transferido`). | • `Contrato`              |
| • Registrar los datos de la transferencia bancaria realizada al propietario.                        | • `Usuario` (Propietario) |

---

### 6. Módulo de Notificaciones y Reportes

#### Tarjeta CRC: `ServicioNotificaciones`

| **ServicioNotificaciones**                                                             |                   |
| -------------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                                  | **Colaboradores** |
| • Generar y enviar alertas de recepción y resolución de pagos.                         | • `Pago`          |
| • Emitir avisos preventivos de vencimiento de cuotas a inquilinos.                     | • `Cuota`         |
| • Alertar a administradores y propietarios sobre vencimientos inminentes de contratos. | • `Contrato`      |

#### Tarjeta CRC: `DashboardReportes`

| **DashboardReportes**                                                           |                   |
| ------------------------------------------------------------------------------- | ----------------- |
| **Responsabilidades**                                                           | **Colaboradores** |
| • Consolidar métricas de ocupación, recaudación, morosidad y saldos a liquidar. | • `Inmueble`      |
| • Generar resúmenes patrimoniales para el portal del propietario.               | • `Cuota`         |
| • Exportar informes de morosos y balances para auditoría contable.              | • `Liquidacion`   |
