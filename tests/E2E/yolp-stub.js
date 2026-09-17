import { createServer } from 'node:http';

createServer((_request, response) => {
    response.writeHead(200, { 'Content-Type': 'application/json' });
    response.end(JSON.stringify({
        Feature: [{
            Geometry: { Coordinates: '139.760000,35.680000' },
            Property: { Address: '東京都千代田区千代田1-1' },
        }],
    }));
}).listen(8081, '127.0.0.1');
