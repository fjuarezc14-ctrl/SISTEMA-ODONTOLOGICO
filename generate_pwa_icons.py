import os
from PIL import Image

assets_dir = r'C:\Users\mejia\.gemini\antigravity\scratch\SISTEMA-ODONTOLOGICO\assets'
root_dir = r'C:\Users\mejia\.gemini\antigravity\scratch\SISTEMA-ODONTOLOGICO'

def create_pwa_icon(size, dest_path):
    # Load the high-res transparent tooth icon
    tooth = Image.open(os.path.join(assets_dir, 'logo_icon.png')).convert('RGBA')
    
    # Create a square white canvas
    canvas = Image.new('RGBA', (size, size), (255, 255, 255, 255))
    
    # We want to center the tooth icon inside the canvas, leaving some padding
    # Let's say padding is 15% of the size
    padding = int(size * 0.15)
    target_size = size - (padding * 2)
    
    # Resize tooth icon keeping aspect ratio
    w, h = tooth.size
    aspect = w / h
    if aspect > 1:
        new_w = target_size
        new_h = int(target_size / aspect)
    else:
        new_h = target_size
        new_w = int(target_size * aspect)
        
    resized_tooth = tooth.resize((new_w, new_h), Image.Resampling.LANCZOS)
    
    # Paste centered
    x = (size - new_w) // 2
    y = (size - new_h) // 2
    canvas.paste(resized_tooth, (x, y), resized_tooth)
    
    # Save as PNG
    canvas.save(dest_path, 'PNG')
    print(f'Generated PWA icon: {dest_path} ({size}x{size})')

if __name__ == '__main__':
    create_pwa_icon(192, os.path.join(root_dir, 'icono-192x192.png'))
    create_pwa_icon(512, os.path.join(root_dir, 'icono-512x512.png'))
