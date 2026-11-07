"""Dependency preflight; intentionally contains no credentials or database access."""
import websocket  # noqa: F401
import pypdfium2  # noqa: F401
import PIL  # noqa: F401

print('Python dependencies OK')
