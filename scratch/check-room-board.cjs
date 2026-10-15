const fs = require('fs');

const env = Object.fromEntries(
    fs.readFileSync('.env', 'utf8')
        .split(/\r?\n/)
        .filter((line) => line && !line.startsWith('#') && line.includes('='))
        .map((line) => {
            const at = line.indexOf('=');
            return [line.slice(0, at), line.slice(at + 1)];
        })
);

async function pause(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function main() {
    const page = await fetch('http://127.0.0.1:9333/json/new?about:blank', { method: 'PUT' }).then((reply) => reply.json());
    const socket = new WebSocket(page.webSocketDebuggerUrl);
    const waiting = new Map();
    let commandId = 0;

    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true });
        socket.addEventListener('error', reject, { once: true });
    });
    socket.addEventListener('message', (event) => {
        const message = JSON.parse(event.data);
        if (message.id && waiting.has(message.id)) {
            const promise = waiting.get(message.id);
            waiting.delete(message.id);
            if (message.error) {
                promise.reject(new Error(message.error.message));
            } else {
                promise.resolve(message.result);
            }
        }
    });

    function send(method, params = {}) {
        commandId += 1;
        return new Promise((resolve, reject) => {
            waiting.set(commandId, { resolve, reject });
            socket.send(JSON.stringify({ id: commandId, method, params }));
        });
    }

    async function evaluate(expression) {
        const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) {
            throw new Error(result.exceptionDetails.text);
        }
        return result.result.value;
    }

    async function waitFor(expression) {
        for (let attempt = 0; attempt < 100; attempt += 1) {
            if (await evaluate(expression)) {
                return;
            }
            await pause(100);
        }
        throw new Error(`Timed out waiting for ${expression}`);
    }

    await send('Page.enable');
    await send('Runtime.enable');
    await send('Emulation.setDeviceMetricsOverride', { width: 1520, height: 1000, deviceScaleFactor: 1, mobile: false });
    await send('Page.navigate', { url: `http://localhost:8080/${env.ADMIN_DIR}/index.php?controller=AdminLogin` });
    await waitFor('document.readyState === "complete"');

    const redirect = await evaluate(`(async () => {
        const body = new URLSearchParams({
            ajax: '1', token: '', controller: 'AdminLogin', submitLogin: '1',
            email: ${JSON.stringify(env.ADMIN_EMAIL)}, passwd: ${JSON.stringify(env.ADMIN_PASSWORD)},
            redirect: 'AdminDashboard'
        });
        const reply = await fetch(location.href, { method: 'POST', body });
        return JSON.parse(await reply.text()).redirect;
    })()`);
    await send('Page.navigate', { url: new URL(redirect.replaceAll('\\/', '/'), `http://localhost:8080/${env.ADMIN_DIR}/`).href });
    await waitFor('document.readyState === "complete" && !!document.querySelector("#nav-sidebar")');
    const roomBoard = await evaluate(`Array.from(document.querySelectorAll('a')).map((a) => a.href).find((href) => href.includes('controller=AdminSalisbergRoomBoard')) || ''`);
    if (!roomBoard) {
        throw new Error('Room Board link was not found');
    }
    await send('Page.navigate', { url: roomBoard });
    await waitFor('document.readyState === "complete" && !!document.querySelector(".sb-room-card")');
    await pause(500);

    const metrics = await evaluate(`(() => {
        const panel = document.querySelector('.sb-selected-room');
        const firstTitle = document.querySelector('.sb-room-group-title');
        const heading = firstTitle.querySelector('h3').getBoundingClientRect();
        const count = firstTitle.querySelector('.sb-room-count').getBoundingClientRect();
        const overflowingValues = Array.from(panel.querySelectorAll('dd')).filter((item) => item.scrollWidth > item.clientWidth + 1).map((item) => item.id || item.textContent.trim());
        const panelRect = panel.getBoundingClientRect();
        return {
            viewport: [innerWidth, innerHeight],
            pageWidth: [document.documentElement.clientWidth, document.documentElement.scrollWidth],
            panelWidth: Math.round(panelRect.width),
            panelInsideViewport: panelRect.right <= document.documentElement.clientWidth,
            headingCountGap: Math.round(count.left - heading.right),
            groupCount: firstTitle.querySelector('.sb-room-count').textContent.trim(),
            overflowingValues,
            nightlyRatesVisible: !document.querySelector('#sb-selected-nightly').hidden,
            selectedTotal: document.querySelector('#sb-selected-total').textContent.trim()
        };
    })()`);
    const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    fs.writeFileSync('scratch/room-board-1520.png', Buffer.from(shot.data, 'base64'));

    await send('Emulation.setDeviceMetricsOverride', { width: 1100, height: 900, deviceScaleFactor: 1, mobile: false });
    await pause(300);
    metrics.medium = await evaluate(`(() => ({
        pageWidth: [document.documentElement.clientWidth, document.documentElement.scrollWidth],
        boardColumns: getComputedStyle(document.querySelector('.sb-board-layout')).gridTemplateColumns,
        mobileActionVisible: getComputedStyle(document.querySelector('.sb-mobile-booking')).display !== 'none'
    }))()`);
    socket.close();
    process.stdout.write(JSON.stringify(metrics, null, 2));
}

main().catch((error) => {
    process.stderr.write(`${error.stack}\n`);
    process.exit(1);
});
