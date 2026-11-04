import { test, expect } from '@playwright/test';

// Browser plugin not available. Read-only smoke checks against the installed LAN app.
test('gerencia: acceso, venta rápida, costos y vista móvil', async ({ page }) => {
    test.skip(!process.env.DAMI_QA_PASSWORD, 'Supply credentials explicitly; never reset a real user password for QA.');
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').fill(process.env.DAMI_QA_EMAIL || 'damiancompany@damiancompany.com.pe');
    await page.locator('input[type="password"]').fill(process.env.DAMI_QA_PASSWORD);
    await page.getByRole('button', { name: 'Ingresar', exact: true }).click();
    await expect(page).toHaveURL(/\/admin$/);
    for (const path of ['/admin/users', '/admin/printers', '/admin/dami-orders']) {
        const response=await page.goto(path);
        expect(response.status()).toBe(200);
        await expect(page.locator('main')).not.toContainText('Server Error');
    }
    await page.goto('/admin/workshop');
    await expect(page.getByRole('heading', { name: 'Operación DAMI 3D' })).toBeVisible();
    await expect(page.getByRole('heading', { name: /D3D-202607-0001/ })).toBeVisible();
    await page.getByRole('button', { name: 'Nueva venta / pedido', exact: true }).first().click();
    const dialog = page.locator('.fi-modal-window').filter({ has: page.getByRole('heading', { name: 'Nueva venta / pedido', exact: true }) }).last();
    await expect(dialog).toBeVisible();
    await dialog.getByLabel('Descripción').fill('Solo revisión de interfaz, no guardar');
    await dialog.getByLabel('Cantidad').fill('2');
    await dialog.getByLabel('Precio unitario').fill('12.50');
    await dialog.getByLabel('Precio unitario').blur();
    await expect(dialog.getByText('Total: S/ 25.00')).toBeVisible();
    await expect(dialog.getByRole('button', { name: '🎙 Dictar' }).first()).toBeDisabled();
    await page.screenshot({ path: 'test-results/workshop-sale-desktop.png', fullPage: false, animations:'disabled' });
    await dialog.getByRole('button', { name: 'Cancelar', exact: true }).last().click();
    await page.getByRole('button', { name: 'Costos y configuración', exact: true }).click();
    await page.getByRole('button', { name: 'Nueva tarifa / vigencia', exact: true }).click();
    await expect(page.getByText('Modo excluyente', { exact: false })).toBeVisible();
    await page.keyboard.press('Escape');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('button', { name: 'Ventas y pedidos', exact: true }).click();
    await expect(page.getByRole('heading', { name: /D3D-202607-0001/ })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Nueva venta / pedido', exact: true }).first()).toBeVisible();
    await page.screenshot({ path: 'test-results/workshop-mobile.png', fullPage: false, animations:'disabled' });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 2)).toBeTruthy();
    expect(errors).toEqual([]);
});

test('trabajador: opciones operativas y escritura manual sin costos internos', async ({ browser }) => {
    test.skip(!process.env.DAMI_QA_WORKER_STATE, 'Use an explicitly prepared short-lived QA session.');
    const context = await browser.newContext({ storageState: process.env.DAMI_QA_WORKER_STATE });
    const page = await context.newPage(); const errors=[];
    page.on('pageerror', error=>errors.push(error.message));
    await page.goto('http://192.168.18.25/admin/workshop');
    await expect(page.getByRole('heading',{name:'¿Qué vas a registrar?'})).toBeVisible();
    await expect(page.getByRole('button',{name:'Reportes',exact:true})).toHaveCount(0);
    await expect(page.getByRole('button',{name:'Costos y configuración',exact:true})).toHaveCount(0);
    await expect(page.getByText('Costo confirmado registrado:')).toHaveCount(0);
    await page.getByRole('button',{name:'Fondos y rendiciones',exact:true}).click();
    await expect(page).toHaveURL(/tab=funds/);
    await expect(page.getByRole('button',{name:'Asignar fondos',exact:true})).toHaveCount(0);
    await page.getByRole('button',{name:'Registrar producción',exact:true}).first().click();
    const modal=page.locator('.fi-modal-window').filter({has:page.getByRole('heading',{name:'Registrar producción',exact:true})});
    await expect(modal.getByLabel('Descripción')).toBeVisible();
    await modal.getByLabel('Descripción').fill('Revisión manual sin guardar');
    await expect(modal.getByLabel('Descripción')).toHaveValue('Revisión manual sin guardar');
    await page.screenshot({path:'test-results/workshop-worker-production.png',fullPage:false,animations:'disabled'});
    await page.keyboard.press('Escape');
    expect(errors).toEqual([]); await context.close();
});
