import { useEffect } from "react"
import { Canvas, useThree } from "@react-three/fiber"
import AvatarModel from "./AvatarModel"
import { SKY } from "./catalog"

const SIZE = 256

// Dopo qualche fotogramma (modelli e luci pronti) salva l'immagine del canvas.
function Snapshot({ onCaptured }) {
  const gl = useThree((s) => s.gl)

  useEffect(() => {
    let frames = 0
    let id
    const tick = () => {
      frames++
      // Aspetta che il canvas abbia la sua misura e che la scena sia disegnata (max ~2 secondi).
      const ready = gl.domElement.width >= SIZE / 2 && frames >= 6
      if (!ready && frames < 120) {
        id = requestAnimationFrame(tick)
        return
      }
      gl.domElement.toBlob((blob) => onCaptured(blob), "image/png")
    }
    id = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(id)
  }, [gl, onCaptured])

  return null
}

/**
 * Ritratto (testa e spalle) dell'avatar, disegnato fuori schermo e restituito
 * come immagine: e' quello che vedono gli altri in sfide e classifiche.
 */
export default function PortraitCapture({ config, onCaptured }) {
  const sky = SKY[config?.equipped?.background] || SKY.default

  return (
    <div aria-hidden="true" style={{ position: "fixed", left: -10000, top: 0, width: SIZE, height: SIZE, pointerEvents: "none" }}>
      <Canvas
        dpr={1}
        gl={{ preserveDrawingBuffer: true, antialias: true }}
        camera={{ position: [0, 1.34, 1.65], fov: 30 }}
        onCreated={({ camera }) => camera.lookAt(0, 1.29, 0)}
      >
        <color attach="background" args={[sky]} />
        <ambientLight intensity={1.3} />
        <directionalLight position={[2.5, 4, 3]} intensity={1.6} />
        <directionalLight position={[-3, 2, -2]} intensity={0.5} color="#b8c4ff" />
        <AvatarModel config={config} animate={false} />
        <Snapshot onCaptured={onCaptured} />
      </Canvas>
    </div>
  )
}
