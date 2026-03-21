function LoaderButton({ loading, children, ...props }) {
  return (
    <button {...props} disabled={loading || props.disabled}>
      {loading ? (
        <>
          <span className="spinner-border spinner-border-sm me-2"></span>
          Caricamento...
        </>
      ) : (
        children
      )}
    </button>
  )
}

export default LoaderButton