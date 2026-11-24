import AppKit

let base = URL(fileURLWithPath: FileManager.default.currentDirectoryPath)
let branding = base.appendingPathComponent("public/branding", isDirectory: true)

func renderSVG(_ source: String, name: String, width: Int, height: Int, fit: NSRect? = nil) throws {
    guard let image = NSImage(contentsOf: branding.appendingPathComponent(source)) else {
        throw NSError(domain: "ATARA", code: 1, userInfo: [NSLocalizedDescriptionKey: "Cannot read \(source)"])
    }
    let bitmap = NSBitmapImageRep(bitmapDataPlanes: nil, pixelsWide: width, pixelsHigh: height,
                                  bitsPerSample: 8, samplesPerPixel: 4, hasAlpha: true,
                                  isPlanar: false, colorSpaceName: .deviceRGB,
                                  bytesPerRow: 0, bitsPerPixel: 0)!
    bitmap.size = NSSize(width: width, height: height)
    let context = NSGraphicsContext(bitmapImageRep: bitmap)!
    NSGraphicsContext.saveGraphicsState()
    NSGraphicsContext.current = context
    context.imageInterpolation = .high
    image.draw(in: fit ?? NSRect(x: 0, y: 0, width: width, height: height),
               from: NSRect(origin: .zero, size: image.size), operation: .sourceOver, fraction: 1)
    context.flushGraphics()
    NSGraphicsContext.restoreGraphicsState()
    let data = bitmap.representation(using: .png, properties: [:])!
    try data.write(to: branding.appendingPathComponent(name), options: .atomic)
}

for size in [64, 128, 256, 512, 1024] {
    let logoWidth = Double(size) * 0.88
    let logoHeight = logoWidth * (100.0 / 120.0)
    let rect = NSRect(x: (Double(size) - logoWidth) / 2,
                      y: (Double(size) - logoHeight) / 2,
                      width: logoWidth, height: logoHeight)
    try renderSVG("atara-mark.svg", name: "atara-mark-\(size).png", width: size, height: size, fit: rect)
}
for width in [360, 720, 1440] {
    try renderSVG("atara-logo.svg", name: "atara-logo-\(width).png", width: width, height: width * 250 / 720)
}
